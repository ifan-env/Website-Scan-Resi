"""
Service Inference Model SVM — Loker Paket Pintar
Jalankan: pip install -r requirements.txt, lalu python ml_service/app.py
-> http://127.0.0.1:5000
Endpoint: GET /health, POST /predict (field "file" / JSON gambar_base64)
PATCH baca nomor: bila SVM = ada_resi, lanjut tahap ke-2
(baca_nomor.py). Respons menambah: nomor_resi, metode_baca,
label_ketemu, teks_baca. Matikan: POST /predict?baca_nomor=0
"""
import base64
import io
import os
import time
from pathlib import Path

import cv2
import joblib
import numpy as np
from flask import Flask, jsonify, request
from PIL import Image
from skimage.feature import hog

try:
    from baca_nomor import baca_nomor_dari_gambar
    BACA_NOMOR_TERSEDIA = True
except Exception as _e_baca:
    baca_nomor_dari_gambar = None
    BACA_NOMOR_TERSEDIA = False
    print(f"Peringatan: modul baca_nomor tidak bisa dimuat: {_e_baca}")

BASE_DIR = Path(__file__).resolve().parent
PROJECT_DIR = BASE_DIR.parent


def cari_model() -> Path:
    kandidat = [
        os.environ.get("MODEL_PATH"),
        PROJECT_DIR / "projek-semester5" / "model_svm_seri.pkl",
        PROJECT_DIR / "projek-semester5" / "model_svm_resi.pkl",
        BASE_DIR / "model_svm_resi.pkl",
    ]
    for kandidat_path in kandidat:
        if kandidat_path and Path(kandidat_path).exists():
            return Path(kandidat_path)
    raise FileNotFoundError(
        "Model .pkl tidak ditemukan. Letakkan model_svm_resi.pkl di folder "
        "projek-semester5/ atau set environment variable MODEL_PATH."
    )


MODEL_PATH = cari_model()
_paket = joblib.load(MODEL_PATH)
MODEL = _paket["model"] if isinstance(_paket, dict) else _paket
KELAS = (_paket.get("kelas") if isinstance(_paket, dict) else None) \
    or list(getattr(MODEL, "classes_", []))
JUMLAH_FITUR = int(getattr(MODEL, "n_features_in_", 1812))
NAMA_TAMPIL = {"ada_resi": "Ada Resi", "tidak_ada_resi": "Tidak Ada Resi"}
app = Flask(__name__)
app.config["MAX_CONTENT_LENGTH"] = 10 * 1024 * 1024


def fitur_dari_gambar(img_rgb: Image.Image) -> np.ndarray:
    abu = np.asarray(img_rgb.convert("L"))
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
    olah = cv2.bilateralFilter(clahe.apply(abu), d=9, sigmaColor=75, sigmaSpace=75)
    kecil = np.asarray(Image.fromarray(olah).resize((64, 64)), dtype=float) / 255.0
    f_hog = hog(kecil, orientations=9, pixels_per_cell=(8, 8),
                cells_per_block=(2, 2), visualize=False)
    arr = np.asarray(img_rgb.resize((64, 64)), dtype=float) / 255.0
    hist = np.concatenate([np.histogram(arr[:, :, c], bins=16, range=(0, 1))[0]
                           for c in range(3)]).astype(float)
    hist = hist / (hist.sum() + 1e-9)
    return np.concatenate([f_hog, hist])


def prediksi(img_rgb: Image.Image, t0: float, baca_nomor: bool = True) -> dict:
    X = fitur_dari_gambar(img_rgb).reshape(1, -1)
    label = str(MODEL.predict(X)[0])
    probabilitas = {}
    if hasattr(MODEL, "predict_proba"):
        for k, p in zip(KELAS, MODEL.predict_proba(X)[0]):
            probabilitas[str(k)] = round(float(p), 4)
    elif hasattr(MODEL, "decision_function"):
        skor = np.atleast_1d(MODEL.decision_function(X))[0]
        p = 1.0 / (1.0 + np.exp(-float(skor)))
        for k, nilai in zip(KELAS, [1 - p, p]):
            probabilitas[str(k)] = round(float(nilai), 4)
    nomor = {"nomor_resi": None, "metode_baca": None,
             "label_ketemu": None, "teks_baca": None}
    if label == "ada_resi" and baca_nomor and BACA_NOMOR_TERSEDIA:
        try:
            img_bgr = cv2.cvtColor(np.asarray(img_rgb), cv2.COLOR_RGB2BGR)
            hasil_baca = baca_nomor_dari_gambar(img_bgr)
            nomor = {
                "nomor_resi": hasil_baca.get("nomor_resi"),
                "metode_baca": hasil_baca.get("metode_baca"),
                "label_ketemu": hasil_baca.get("label_ketemu"),
                "teks_baca": hasil_baca.get("teks_baca"),
            }
        except Exception:
            pass
    return {
        "prediksi": label,
        "nama_kelas": NAMA_TAMPIL.get(label, label),
        "kepercayaan": probabilitas.get(label),
        "probabilitas": probabilitas,
        "jumlah_fitur": int(X.shape[1]),
        **nomor,
        "waktu_proses_detik": round(time.time() - t0, 3),
    }


@app.get("/")
def info():
    return jsonify({
        "nama": "Service Inference Model SVM — Loker Paket Pintar",
        "model": MODEL_PATH.name, "kelas": KELAS,
        "jumlah_fitur": JUMLAH_FITUR, "baca_nomor": BACA_NOMOR_TERSEDIA,
        "endpoint": {
            "GET /health": "cek kesiapan service & model",
            "POST /predict": "upload gambar (field 'file') atau JSON {'gambar_base64': '...'}",
        },
    })


@app.get("/health")
def health():
    return jsonify({
        "status": "ok", "model": MODEL_PATH.name, "kelas": KELAS,
        "jumlah_fitur": JUMLAH_FITUR, "baca_nomor": BACA_NOMOR_TERSEDIA,
    })


@app.post("/predict")
def predict():
    t0 = time.time()
    img = None
    if "file" in request.files and request.files["file"].filename:
        try:
            img = Image.open(request.files["file"].stream).convert("RGB")
        except Exception:
            return jsonify({"error": "Berkas bukan gambar yang valid."}), 400
    elif request.is_json and request.json and "gambar_base64" in request.json:
        try:
            mentah = base64.b64decode(request.json["gambar_base64"])
            img = Image.open(io.BytesIO(mentah)).convert("RGB")
        except Exception:
            return jsonify({"error": "Data base64 tidak bisa dibaca sebagai gambar."}), 400
    if img is None:
        return jsonify({"error": "Kirim gambar lewat form field 'file' atau JSON 'gambar_base64'."}), 400
    baca_nomor = request.args.get("baca_nomor", "1") != "0"
    if request.is_json and request.json and "baca_nomor" in request.json:
        baca_nomor = bool(request.json["baca_nomor"])
    try:
        return jsonify(prediksi(img, t0, baca_nomor=baca_nomor))
    except Exception as e:
        return jsonify({"error": f"Gagal memproses gambar: {e}"}), 422


@app.errorhandler(413)
def terlalu_besar(_):
    return jsonify({"error": "Ukuran gambar melebihi 10 MB."}), 413


if __name__ == "__main__":
    port = int(os.environ.get("ML_PORT", "5000"))
    print(f"Model: {MODEL_PATH} | Kelas: {KELAS} | Fitur: {JUMLAH_FITUR}")
    print(f"URL: http://127.0.0.1:{port}")
    app.run(host="127.0.0.1", port=port, debug=False)
