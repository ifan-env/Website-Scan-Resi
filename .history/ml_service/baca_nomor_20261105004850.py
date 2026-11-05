"""
Baca nomor resi dari gambar paket — tahap ke-2 setelah SVM. VERSI 2.

Basisnya v1 (port deteksi_resi_v2.py, 29 Okt 2026): deteksi label
dan seluruh parameter preprocessing-nya TIDAK diubah. Yang berubah
adalah STRATEGI MEMBACA, karena uji lapangan 4-5 Nov 2026 pada web
pengguna menunjukkan tiga kegagalan berurutan:

  1. QR di label SPX tidak tertangkap. Ternyata decoder OpenCV dan
     pyzbar sama-sama bisa membacanya dari FOTO UTUH resolusi asli,
     tetapi v1 hanya mencoba decode pada crop hasil warp label —
     dan bila label ketemu, foto utuh tidak pernah dicoba.
  2. OCR membaca nomor SPXID06024744489A menjadi XID060247444004:
     awalan hilang, 3 karakter ekor salah. Satu kali OCR psm 3 pada
     satu crop memang serapuh itu.
  3. Nomor hasil OCR yang nyaris benar tetap tidak berguna bagi
     loker bila dibandingkan persis (string eksak) — padahal loker
     punya daftar resi yang ditunggu, dan daftar itu bisa dipakai
     untuk memutuskan, bukan menebak.

Strategi v2 (berurutan, berhenti di keberhasilan pertama yang sah):
  A. Decode kode pada FOTO UTUH: pyzbar (bila terpasang) lalu
     QRCodeDetector + BarcodeDetector OpenCV; tiap mesin dicoba
     pada gambar asli dan versi diperbesar.
  B. Deteksi label seperti v1 -> warp -> deskew -> decode kode
     pada ROI (mesin yang sama, ROI asli + diperbesar).
  C. OCR multi-percobaan: ROI psm 3, ROI psm 11, separuh bawah ROI
     (di situlah label SPX/J&T mencetak nomor besar), lalu foto
     utuh psm 3 dan foto utuh diputar 180 derajat. Semua kandidat
     yang cocok pola dikumpulkan, yang terpanjang menang.
  D. Bila pemanggil memberi `daftar_tunggu`, kandidat terbaik
     dicocokkan secara toleran ke daftar itu (jarak Levenshtein):
     diterima HANYA bila satu kandidat jelas paling dekat
     (jarak <= min(6, 35% panjang nomor) dan unggul >= 2 dari
     kandidat daftar berikutnya). Selain itu statusnya dilaporkan
     apa adanya ("ambigu" / "tidak_ada") dan nomor resmi None —
     tahap ini tidak pernah mengarang nomor.

Hasil ukur acuan tetap sama: v2 di dataset fix (89 foto) label
ketemu 87/89, nomor dikenali pola 65/89 (73%). Nomor boleh None —
pemanggil WAJIB menyediakan koreksi manual.

Kebutuhan:
  - opencv (QRCodeDetector + barcode bila modulnya ada)
  - OPSIONAL pyzbar + library zbar sistem (Mac: brew install zbar;
    Windows: wheel pyzbar membawa DLL-nya; Linux: libzbar0).
    Tanpa pyzbar modul tetap jalan, mesin OpenCV yang bekerja.
  - binary `tesseract` HANYA untuk jalur OCR.
"""

import math
import os
import re
import shutil
import subprocess
import tempfile

import cv2
import numpy as np

try:
    from pyzbar.pyzbar import decode as _zbar_decode
    PYZBAR_TERSEDIA = True
except Exception:
    _zbar_decode = None
    PYZBAR_TERSEDIA = False

# ---- parameter deteksi label: sama persis dengan v1 / deteksi_resi_v2 ----
CANNY_RENDAH, CANNY_TINGGI = 50, 150
LUAS_MIN, LUAS_MAKS = 0.02, 0.85
CERAH_MIN = 0.35
CLAHE_CLIP, CLAHE_TILE = 2.0, (8, 8)
BILATERAL_D, BILATERAL_WARNA, BILATERAL_RUANG = 9, 75, 75

# Pola nomor resi: huruf 2-5 + digit 6-16 + huruf ekor opsional
# (resi SPX berakhiran huruf, mis. SPXID06024744489A), atau digit saja.
POLA_RESI = re.compile(r"\b([A-Z]{2,5}\d{6,16}[A-Z]?|\d{10,20})\b")


def normalisasi_resi(resi):
    """Huruf kapital, buang semua non-alfanumerik."""
    if not resi:
        return None
    bersih = re.sub(r"[^A-Z0-9]", "", str(resi).upper())
    return bersih or None


def semua_kandidat_resi(teks):
    """Semua kandidat nomor yang cocok pola dari teks, terpanjang dulu."""
    if not teks:
        return []
    bersih = re.sub(r"[^A-Z0-9\s]", " ", str(teks).upper())
    kandidat = set(POLA_RESI.findall(bersih))
    kandidat |= set(POLA_RESI.findall(re.sub(r"\s+", "", bersih)))
    return sorted(kandidat, key=len, reverse=True)


def ekstrak_resi_dari_teks(teks):
    """Kandidat terpanjang yang cocok pola (perilaku v1)."""
    kandidat = semua_kandidat_resi(teks)
    return kandidat[0] if kandidat else None


# ------------------------------------------------------------------
# Pencocokan toleran ke daftar tunggu
# ------------------------------------------------------------------

def jarak_levenshtein(a, b):
    """Jarak edit klasik (sisip/hapus/ganti = 1)."""
    if a == b:
        return 0
    if not a:
        return len(b)
    if not b:
        return len(a)
    prev = list(range(len(b) + 1))
    for i, ca in enumerate(a, 1):
        cur = [i]
        for j, cb in enumerate(b, 1):
            cur.append(min(prev[j] + 1, cur[j - 1] + 1,
                           prev[j - 1] + (0 if ca == cb else 1)))
        prev = cur
    return prev[-1]


def cocokkan_ke_daftar(kandidat, daftar_tunggu):
    """Putuskan nomor resmi dari kandidat mentah + daftar tunggu.

    kandidat: list string mentah (dari QR/barcode/OCR, belum tentu
    ternormalisasi). daftar_tunggu: list nomor resmi yang sah.

    Kembalikan dict {nomor, jarak, kandidat_sumber, status} dengan
    status "eksak" | "toleran" | "ambigu" | "tidak_ada". Nomor hanya
    terisi pada status eksak/toleran. Aturan terima toleran:
      jarak <= min(6, ceil(0,35 * panjang nomor resmi))
      DAN (daftar cuma 1 entri ATAU unggul >= 2 dari entri terdekat
      berikutnya). Aturan ini yang membuat tahap ini memutuskan dari
      daftar, bukan mengarang nomor baru.
    """
    daftar = []
    for d in daftar_tunggu or []:
        n = normalisasi_resi(d)
        if n and n not in daftar:
            daftar.append(n)
    mentah = []
    for k in kandidat or []:
        n = normalisasi_resi(k)
        if n and n not in mentah:
            mentah.append(n)
    if not daftar or not mentah:
        return {"nomor": None, "jarak": None, "kandidat_sumber": None,
                "status": "tidak_ada"}

    terbaik = None  # (jarak, nomor_resmi, sumber, jarak_kedua)
    for sumber in mentah:
        terurut = sorted((jarak_levenshtein(sumber, resmi), resmi)
                         for resmi in daftar)
        jarak1, resmi = terurut[0]
        jarak2 = terurut[1][0] if len(terurut) > 1 else None
        if terbaik is None or jarak1 < terbaik[0]:
            terbaik = (jarak1, resmi, sumber, jarak2)

    jarak, resmi, sumber, jarak2 = terbaik
    if jarak == 0:
        return {"nomor": resmi, "jarak": 0, "kandidat_sumber": sumber,
                "status": "eksak"}
    batas = min(6, math.ceil(0.35 * len(resmi)))
    if jarak > batas:
        return {"nomor": None, "jarak": jarak, "kandidat_sumber": sumber,
                "status": "tidak_ada"}
    if jarak2 is not None and jarak2 - jarak < 2:
        return {"nomor": None, "jarak": jarak, "kandidat_sumber": sumber,
                "status": "ambigu"}
    return {"nomor": resmi, "jarak": jarak, "kandidat_sumber": sumber,
            "status": "toleran"}


# ------------------------------------------------------------------
# Deteksi label — sama persis dengan v1
# ------------------------------------------------------------------

def _urutkan_sudut(poli):
    titik = poli.reshape(4, 2).astype(np.float32)
    jumlah = titik.sum(axis=1)
    selisih = np.diff(titik, axis=1).ravel()
    return np.array([titik[np.argmin(jumlah)],
                     titik[np.argmin(selisih)],
                     titik[np.argmax(jumlah)],
                     titik[np.argmax(selisih)]], dtype=np.float32)


def _prapemrosesan(abu):
    halus = cv2.bilateralFilter(abu, BILATERAL_D, BILATERAL_WARNA, BILATERAL_RUANG)
    clahe = cv2.createCLAHE(clipLimit=CLAHE_CLIP, tileGridSize=CLAHE_TILE)
    return clahe.apply(halus)


def _deskew(roi):
    abu = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY) if roi.ndim == 3 else roi
    _, biner = cv2.threshold(abu, 0, 255, cv2.THRESH_OTSU)
    biner = cv2.bitwise_not(biner)
    garis = cv2.HoughLinesP(biner, 1, np.pi / 180, threshold=100,
                            minLineLength=max(40, roi.shape[1] // 4),
                            maxLineGap=20)
    if garis is None:
        return roi, 0.0
    sudut_list = []
    for x1, y1, x2, y2 in garis.reshape(-1, 4):
        if x2 == x1:
            continue
        sudut = np.degrees(np.arctan2(y2 - y1, x2 - x1))
        if abs(sudut) < 30:
            sudut_list.append(sudut)
    if not sudut_list:
        return roi, 0.0
    sudut = float(np.median(sudut_list))
    if abs(sudut) < 0.5:
        return roi, 0.0
    h, w = roi.shape[:2]
    matriks = cv2.getRotationMatrix2D((w / 2, h / 2), sudut, 1.0)
    lurus = cv2.warpAffine(roi, matriks, (w, h), borderMode=cv2.BORDER_REPLICATE)
    return lurus, round(sudut, 2)


def _cari_roi_label(img_bgr):
    """Deteksi segiempat label persis v1.

    Kembalikan (roi_bgr, info) atau (None, info); info berisi
    luas_fraksi, fraksi_cerah, sudut_deskew bila label ketemu.
    """
    tinggi, lebar = img_bgr.shape[:2]
    abu = cv2.cvtColor(img_bgr, cv2.COLOR_BGR2GRAY)
    siap = _prapemrosesan(abu)
    tepi = cv2.Canny(siap, CANNY_RENDAH, CANNY_TINGGI)
    kernel = np.ones((5, 5), np.uint8)
    tertutup = cv2.morphologyEx(tepi, cv2.MORPH_CLOSE, kernel, iterations=2)
    kontur = sorted(list(cv2.findContours(tertutup, cv2.RETR_EXTERNAL,
                                          cv2.CHAIN_APPROX_SIMPLE)[0]),
                    key=cv2.contourArea, reverse=True)
    for c in kontur:
        fraksi = cv2.contourArea(c) / (tinggi * lebar)
        if fraksi < LUAS_MIN or fraksi > LUAS_MAKS:
            continue
        hull = cv2.convexHull(c)
        poli = cv2.approxPolyDP(hull, 0.02 * cv2.arcLength(hull, True), True)
        if len(poli) != 4:
            rect = cv2.minAreaRect(c)
            poli = cv2.boxPoints(rect).reshape(4, 1, 2).astype(np.int32)
        mask = np.zeros((tinggi, lebar), np.uint8)
        cv2.drawContours(mask, [poli], -1, 255, -1)
        isi = abu[mask == 255]
        fraksi_cerah = float((isi > 160).mean()) if isi.size else 0.0
        if fraksi_cerah < CERAH_MIN:
            continue
        sudut = _urutkan_sudut(poli)
        lebar_roi = int(max(np.linalg.norm(sudut[0] - sudut[1]),
                            np.linalg.norm(sudut[2] - sudut[3])))
        tinggi_roi = int(max(np.linalg.norm(sudut[1] - sudut[2]),
                             np.linalg.norm(sudut[3] - sudut[0])))
        lebar_roi, tinggi_roi = max(lebar_roi, 40), max(tinggi_roi, 40)
        tujuan = np.array([[0, 0], [lebar_roi - 1, 0],
                           [lebar_roi - 1, tinggi_roi - 1], [0, tinggi_roi - 1]],
                          dtype=np.float32)
        roi = cv2.warpPerspective(img_bgr, cv2.getPerspectiveTransform(sudut, tujuan),
                                  (lebar_roi, tinggi_roi))
        roi_lurus, sudut_miring = _deskew(roi)
        return roi_lurus, {"luas_fraksi": round(fraksi, 3),
                           "fraksi_cerah": round(fraksi_cerah, 3),
                           "sudut_deskew": sudut_miring}
    return None, {}


# ------------------------------------------------------------------
# Mesin pembaca kode: pyzbar + OpenCV, multi-varian
# ------------------------------------------------------------------

def _varian(img):
    """Gambar asli + versi 2x bila sisi terpanjangnya < 1600 piksel."""
    hasil = [img]
    if max(img.shape[:2]) < 1600:
        hasil.append(cv2.resize(img, None, fx=2, fy=2,
                                interpolation=cv2.INTER_CUBIC))
    return hasil


def _decode_pyzbar(img):
    """Semua simbol zbar pada satu citra -> list (isi, 'QR'|'barcode')."""
    if not PYZBAR_TERSEDIA:
        return []
    try:
        abu = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY) if img.ndim == 3 else img
        ketemu = []
        for r in _zbar_decode(abu):
            isi = r.data.decode("utf-8", "replace").strip()
            if isi:
                ketemu.append((isi, "QR" if r.type == "QRCODE" else "barcode"))
        return ketemu
    except Exception:
        return []


def _decode_opencv(img):
    """QRCodeDetector (tunggal+multi) lalu BarcodeDetector OpenCV."""
    try:
        det = cv2.QRCodeDetector()
        data, _, _ = det.detectAndDecode(img)
        if data:
            return [(data.strip(), "QR")]
        try:
            ok, data_multi, _, _ = det.detectAndDecodeMulti(img)
            if ok and data_multi:
                return [(d.strip(), "QR") for d in data_multi if d]
        except Exception:
            pass
    except Exception:
        pass
    for pembuat in (
        lambda: cv2.barcode_BarcodeDetector(),
        lambda: cv2.barcode.BarcodeDetector(),
    ):
        try:
            detb = pembuat()
            hasil = detb.detectAndDecode(img)
            ok, decoded = hasil[0], hasil[1]
            if ok and decoded:
                daftar = decoded if isinstance(decoded, (list, tuple)) else [decoded]
                return [(str(d).strip(), "barcode") for d in daftar if str(d).strip()]
        except Exception:
            continue
    return []


def _baca_kode_semua(img):
    """Coba semua mesin pada semua varian satu citra.

    Kembalikan (isi, cara, mesin) keberhasilan pertama, atau
    (None, None, None). Urutan: pyzbar (paling agresif membaca QR
    kecil + barcode Code128), baru OpenCV.
    """
    for varian in _varian(img):
        for isi, cara in _decode_pyzbar(varian):
            return isi, cara, "pyzbar"
        for isi, cara in _decode_opencv(varian):
            return isi, cara, "OpenCV"
    return None, None, None


# ------------------------------------------------------------------
# OCR multi-percobaan
# ------------------------------------------------------------------

def _ocr_satu(img, psm):
    """Satu kali OCR Tesseract (binary sistem) pada satu citra."""
    if shutil.which("tesseract") is None:
        return None
    try:
        abu = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY) if img.ndim == 3 else img
        besar = cv2.resize(abu, None, fx=3, fy=3, interpolation=cv2.INTER_CUBIC)
        _, biner = cv2.threshold(besar, 0, 255, cv2.THRESH_OTSU)
        kernel = np.ones((3, 3), np.uint8)
        biner = cv2.morphologyEx(biner, cv2.MORPH_CLOSE, kernel, iterations=1)
        biner = cv2.morphologyEx(biner, cv2.MORPH_OPEN, kernel, iterations=1)
        with tempfile.NamedTemporaryFile(suffix=".png", delete=False) as tmp:
            cv2.imwrite(tmp.name, biner)
            jalan = tmp.name
        try:
            keluar = subprocess.run(["tesseract", jalan, "stdout",
                                     "--psm", str(psm)],
                                    capture_output=True, text=True, timeout=30)
        finally:
            os.unlink(jalan)
        teks = " ".join(keluar.stdout.split())
        return teks if len(teks) > 20 else None
    except Exception:
        return None


def _kumpulkan_kandidat_ocr(roi, img_utuh):
    """Jalankan rangkaian OCR, kumpulkan (kandidat, teks_sumber).

    Urutan berhenti begitu ada kandidat: ROI psm 3 -> ROI psm 11 ->
    separuh bawah ROI psm 6 -> foto utuh psm 3 -> foto utuh diputar
    180 derajat psm 3 (label terbalik seperti uji SPX pertama).
    """
    percobaan = []
    if roi is not None:
        h = roi.shape[0]
        percobaan += [(roi, 3), (roi, 11), (roi[h // 2:, :], 6)]
    if img_utuh is not None:
        percobaan += [(img_utuh, 3),
                      (cv2.rotate(img_utuh, cv2.ROTATE_180), 3)]
    teks_terpanjang = None
    for citra, psm in percobaan:
        teks = _ocr_satu(citra, psm)
        if not teks:
            continue
        if teks_terpanjang is None or len(teks) > len(teks_terpanjang):
            teks_terpanjang = teks
        kandidat = semua_kandidat_resi(teks)
        if kandidat:
            return kandidat, teks
    return [], teks_terpanjang


def _saring(mentah, cara):
    """Ubah isi mentah QR/barcode menjadi nomor resi ternormalisasi."""
    if not mentah:
        return None
    ketemu = ekstrak_resi_dari_teks(mentah)
    if ketemu:
        return ketemu
    if cara in ("QR", "barcode"):
        normal = normalisasi_resi(mentah)
        if normal and 6 <= len(normal) <= 25:
            return normal
    return None


# ------------------------------------------------------------------
# Pintu utama
# ------------------------------------------------------------------

def baca_nomor_dari_gambar(img_bgr, daftar_tunggu=None):
    """Baca nomor resi dari gambar BGR di memori (strategi v2).

    daftar_tunggu: list nomor resmi (opsional). Bila diberikan, hasil
    akhir diputuskan oleh cocokkan_ke_daftar() dan dict hasil
    menambah kunci nomor_mentah, status_cocok, jarak_cocok.

    Kunci hasil (superset v1): label_ketemu, nomor_resi, metode_baca
    ("QR"|"barcode"|"OCR"|None), mesin ("pyzbar"|"OpenCV"|"Tesseract"
    |None), teks_baca, kandidat (list mentah), luas_fraksi,
    fraksi_cerah, sudut_deskew.
    """
    hasil = {
        "label_ketemu": False, "nomor_resi": None, "metode_baca": None,
        "mesin": None, "teks_baca": None, "kandidat": [],
        "luas_fraksi": None, "fraksi_cerah": None, "sudut_deskew": None,
    }
    if img_bgr is None or getattr(img_bgr, "size", 0) == 0:
        return hasil

    kandidat_semua = []
    nomor_kode, cara_kode, mesin_kode = None, None, None

    # A. Kode pada foto utuh dulu — pelajaran dari foto SPX 5 Nov:
    #    QR terbaca persis dari foto asli, gagal dari crop warp.
    mentah, cara, mesin = _baca_kode_semua(img_bgr)
    if mentah:
        nomor_kode = _saring(mentah, cara)
        if nomor_kode:
            cara_kode, mesin_kode = cara, mesin
            hasil["teks_baca"] = mentah
            kandidat_semua.append(nomor_kode)

    # B. Deteksi label (untuk laporan + ROI bila kode belum ketemu).
    roi, info = _cari_roi_label(img_bgr)
    if roi is not None:
        hasil["label_ketemu"] = True
        hasil.update(info)
        if nomor_kode is None:
            mentah, cara, mesin = _baca_kode_semua(roi)
            if mentah:
                nomor_kode = _saring(mentah, cara)
                if nomor_kode:
                    cara_kode, mesin_kode = cara, mesin
                    hasil["teks_baca"] = mentah
                    kandidat_semua.append(nomor_kode)

    # C. OCR multi-percobaan bila kode belum menghasilkan nomor.
    teks_ocr = None
    if nomor_kode is None:
        kandidat_ocr, teks_ocr = _kumpulkan_kandidat_ocr(roi, img_bgr)
        kandidat_semua += [k for k in kandidat_ocr if k not in kandidat_semua]
        if hasil["teks_baca"] is None:
            hasil["teks_baca"] = teks_ocr

    # Susun jawaban dasar (tanpa daftar tunggu).
    if nomor_kode is not None:
        hasil["nomor_resi"] = nomor_kode
        hasil["metode_baca"] = cara_kode
        hasil["mesin"] = mesin_kode
    elif kandidat_semua:
        hasil["nomor_resi"] = kandidat_semua[0]
        hasil["metode_baca"] = "OCR"
        hasil["mesin"] = "Tesseract"
    hasil["kandidat"] = kandidat_semua

    # D. Keputusan akhir lewat daftar tunggu bila diberikan.
    if daftar_tunggu is not None:
        mentah_untuk_cocok = list(kandidat_semua)
        if hasil["teks_baca"]:
            mentah_untuk_cocok += semua_kandidat_resi(hasil["teks_baca"])
        putusan = cocokkan_ke_daftar(mentah_untuk_cocok, daftar_tunggu)
        hasil["nomor_mentah"] = hasil["nomor_resi"]
        hasil["status_cocok"] = putusan["status"]
        hasil["jarak_cocok"] = putusan["jarak"]
        hasil["nomor_resi"] = putusan["nomor"]

    return hasil
