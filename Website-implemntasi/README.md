# Loker Paket Pintar — Laravel App

Aplikasi web untuk klasifikasi resi paket menggunakan model SVM
(`../projek-semester5/model_svm_resi.pkl`) melalui service Python Flask.

## Menjalankan

```powershell
# dari root project
.\start.ps1
```

Atau manual:

```powershell
# 1. service model (terminal terpisah)
python ..\ml_service\app.py

# 2. aplikasi Laravel
php artisan serve --host=127.0.0.1 --port=8000
```

Buka **http://localhost:8000** (gunakan `localhost`, bukan domain lain, agar
fitur kamera diizinkan browser).

## Perintah penting

```powershell
php artisan migrate          # buat tabel
php artisan storage:link     # tautkan gambar publik
npm run build                # build Tailwind + JS
php artisan test             # jalankan pengujian
```

Dokumentasi lengkap (arsitektur, API, konfigurasi, troubleshooting) ada di
[`../README.md`](../README.md).

## Konfigurasi

- Database MySQL Laragon: `loker_paket` (user `root`, tanpa password)
- Service model: `ML_SERVICE_URL=http://127.0.0.1:5000`
- Model: `MODEL_PATH=../projek-semester5/model_svm_resi.pkl`
- Dataset contoh: `DATASET_PATH=../projek-semester5/dataset_latih`
