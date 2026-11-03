<?php

namespace App\Http\Controllers;

use App\Exceptions\PrediksiGagalException;
use App\Services\ContohProvider;
use App\Services\PrediksiService;
use Illuminate\Http\Request;

class PrediksiController extends Controller
{
    public function store(Request $request, PrediksiService $service)
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'sumber' => ['nullable', 'in:upload,kamera'],
        ], [
            'file.required' => 'Pilih gambar paket terlebih dahulu.',
            'file.file' => 'Berkas gambar gagal diunggah.',
            'file.image' => 'Berkas harus berupa gambar.',
            'file.mimes' => 'Format gambar harus JPG, PNG, atau WEBP.',
            'file.max' => 'Ukuran gambar maksimal 10 MB.',
            'sumber.in' => 'Sumber gambar tidak valid.',
        ]);

        try {
            $prediksi = $service->dariUpload(
                $request->file('file'),
                $request->input('sumber', 'upload')
            );
        } catch (PrediksiGagalException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        return response()->json([
            'message' => 'Analisis berhasil.',
            'hasil' => $prediksi->toApiArray(),
        ], 201);
    }

    public function contoh(
        Request $request,
        PrediksiService $service,
        ContohProvider $contoh
    ) {
        $data = $request->validate([
            'label' => ['required', 'string'],
            'nama' => ['required', 'string'],
        ]);

        $item = $contoh->cari($data['label'], $data['nama']);

        if ($item === null) {
            return response()->json(['message' => 'Contoh gambar tidak ditemukan.'], 404);
        }

        try {
            $prediksi = $service->dariBerkas(
                $contoh->path($item),
                'contoh',
                $item['label']
            );
        } catch (PrediksiGagalException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        return response()->json([
            'message' => 'Analisis contoh berhasil.',
            'hasil' => $prediksi->toApiArray(),
        ], 201);
    }
}
