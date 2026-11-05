"""
Baca nomor resi dari gambar paket — tahap ke-2 setelah SVM.
Pipeline: grayscale -> Bilateral (9/75/75) -> CLAHE (2.0, 8x8)
-> Canny (50/150) -> closing 5x5 x2 -> kontur -> approxPolyDP
pada convex hull (fallback minAreaRect) -> syarat isi cerah
(fraksi piksel >160 >= 0,35) -> warp perspektif -> deskew
(HoughLinesP) -> baca isi: QR -> barcode -> OCR (--psm 3).
"""
import os
import re
import shutil
import subprocess
import tempfile

import cv2
import numpy as np

CANNY_RENDAH, CANNY_TINGGI = 50, 150
LUAS_MIN, LUAS_MAKS = 0.02, 0.85
CERAH_MIN = 0.35
CLAHE_CLIP, CLAHE_TILE = 2.0, (8, 8)
BILATERAL_D, BILATERAL_WARNA, BILATERAL_RUANG = 9, 75, 75
POLA_RESI = re.compile(r"\b([A-Z]{2,5}\d{6,16}|\d{10,20})\b")


def normalisasi_resi(resi):
    if not resi:
        return None
    bersih = re.sub(r"[^A-Z0-9]", "", str(resi).upper())
    return bersih or None


def ekstrak_resi_dari_teks(teks):
    if not teks:
        return None
    bersih = re.sub(r"[^A-Z0-9\s]", " ", str(teks).upper())
    kandidat = POLA_RESI.findall(bersih)
    if not kandidat:
        kandidat = POLA_RESI.findall(re.sub(r"\s+", "", bersih))
    return max(kandidat, key=len) if kandidat else None


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


def _ocr_roi(roi, adaptif=False):
    if shutil.which("tesseract") is None:
        return None
    try:
        abu = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY) if roi.ndim == 3 else roi
        besar = cv2.resize(abu, None, fx=3, fy=3, interpolation=cv2.INTER_CUBIC)
        if adaptif:
            biner = cv2.adaptiveThreshold(besar, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
                                          cv2.THRESH_BINARY, 51, 10)
        else:
            _, biner = cv2.threshold(besar, 0, 255, cv2.THRESH_OTSU)
        kernel = np.ones((3, 3), np.uint8)
        biner = cv2.morphologyEx(biner, cv2.MORPH_CLOSE, kernel, iterations=1)
        biner = cv2.morphologyEx(biner, cv2.MORPH_OPEN, kernel, iterations=1)
        with tempfile.NamedTemporaryFile(suffix=".png", delete=False) as tmp:
            cv2.imwrite(tmp.name, biner)
            jalan = tmp.name
        try:
            keluar = subprocess.run(["tesseract", jalan, "stdout", "--psm", "3"],
                                    capture_output=True, text=True, timeout=30)
        finally:
            os.unlink(jalan)
        teks = " ".join(keluar.stdout.split())
        return teks if len(teks) > 20 else None
    except Exception:
        return None


def _baca_kode(img):
    try:
        det = cv2.QRCodeDetector()
        data, _, _ = det.detectAndDecode(img)
        if data:
            return data.strip(), "QR"
        try:
            ok, data_multi, _, _ = det.detectAndDecodeMulti(img)
            if ok and data_multi:
                for d in data_multi:
                    if d:
                        return d.strip(), "QR"
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
            ok, decoded, _, _ = detb.detectAndDecode(img) if hasattr(detb, "detectAndDecode") else (False, None, None, None)
            if ok and decoded:
                isi = decoded[0] if isinstance(decoded, (list, tuple)) else str(decoded)
                if isi:
                    return isi.strip(), "barcode"
        except Exception:
            continue
    return None, None


def _saring(mentah, cara):
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


def baca_nomor_dari_gambar(img_bgr):
    hasil_kosong = {
        "label_ketemu": False, "nomor_resi": None, "metode_baca": None,
        "teks_baca": None, "luas_fraksi": None, "fraksi_cerah": None,
        "sudut_deskew": None,
    }
    if img_bgr is None or getattr(img_bgr, "size", 0) == 0:
        return hasil_kosong
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
        mentah, cara = _baca_kode(roi_lurus)
        teks_baca = mentah
        if not mentah:
            teks = _ocr_roi(roi_lurus)
            if teks:
                mentah, cara, teks_baca = teks, "OCR", teks
        return {
            "label_ketemu": True,
            "nomor_resi": _saring(mentah, cara),
            "metode_baca": cara if mentah else None,
            "teks_baca": teks_baca,
            "luas_fraksi": round(fraksi, 3),
            "fraksi_cerah": round(fraksi_cerah, 3),
            "sudut_deskew": sudut_miring,
        }
    mentah, cara = _baca_kode(img_bgr)
    teks_baca = mentah
    if not mentah:
        teks = _ocr_roi(img_bgr)
        if teks:
            mentah, cara, teks_baca = teks, "OCR", teks
    hasil = dict(hasil_kosong)
    hasil.update({
        "nomor_resi": _saring(mentah, cara),
        "metode_baca": cara if mentah else None,
        "teks_baca": teks_baca,
    })
    return hasil
