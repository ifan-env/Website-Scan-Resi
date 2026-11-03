<?php

namespace App\Http\Controllers;

use App\Exceptions\PrediksiGagalException;
use App\Services\InfoModel;
use App\Services\PrediksiService;
use App\Services\PythonPrediksiService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class ApiController extends Controller
{
    public function model(InfoModel $infoModel, PythonPrediksiService $ml)
    {
        $service = $ml->info();

        return response()->json([
            'aplikasi' => config('app.name'),
            'model' => $service['model'] ?? basename(config('prediksi.model_path')),
            'algoritma' => 'SVM RBF (C=5) + StandardScaler',
            'kelas' => config('prediksi.classes'),
            'jumlah_fitur' => $service['jumlah_fitur'] ?? 1812,
            'metrik' => $infoModel->metrik(),
            'service' => [
                'url' => config('prediksi.ml_url'),
                'aktif' => $service !== null,
            ],
            'endpoint' => [
                'GET /api/model' => 'informasi model & metrik',
                'GET /api/health' => 'status aplikasi & service model',
                'POST /api/predict' => "multipart 'file' atau JSON 'gambar_base64'",
            ],
        ]);
    }

    public function health(PythonPrediksiService $ml)
    {
        $info = $ml->info();

        return response()->json([
            'status' => 'ok',
            'aplikasi' => config('app.name'),
            'service_model' => [
                'aktif' => $info !== null,
                'url' => config('prediksi.ml_url'),
                'kelas' => $info['kelas'] ?? null,
                'jumlah_fitur' => $info['jumlah_fitur'] ?? null,
            ],
        ], $info !== null ? 200 : 503);
    }

    public function predict(Request $request, PrediksiService $service)
    {
        try {
            if ($request->hasFile('file')) {
                $request->validate([
                    'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
                ], [
                    'file.required' => 'Pilih gambar paket terlebih dahulu.',
                    'file.image' => 'Berkas harus berupa gambar.',
                    'file.mimes' => 'Format gambar harus JPG, PNG, atau WEBP.',
                    'file.max' => 'Ukuran gambar maksimal 10 MB.',
                ]);

                $prediksi = $service->dariUpload($request->file('file'), 'api');
            } elseif ($request->filled('gambar_base64')) {
                $request->validate([
                    'gambar_base64' => ['required', 'string'],
                ]);

                $prediksi = $service->dariBase64($request->input('gambar_base64'));
            } else {
                return response()->json([
                    'message' => "Kirim gambar lewat multipart field 'file' "
                        ."atau JSON {'gambar_base64': '...'}.",
                ], 422);
            }
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (PrediksiGagalException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memproses gambar.',
            ], 500);
        }

        return response()->json([
            'message' => 'Analisis berhasil.',
            'data' => $prediksi->toApiArray(),
        ], 201);
    }
}
