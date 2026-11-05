<?php

namespace App\Services;

use App\Models\Prediksi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Menghubungkan upload gambar -> service Python -> penyimpanan -> database.
 */
class PrediksiService
{
    public function __construct(private PythonPrediksiService $ml) {}

    public function dariUpload(UploadedFile $file, string $sumber = 'upload'): Prediksi
    {
        $hasil = $this->ml->prediksiFile(
            $file->getRealPath(),
            $file->getClientOriginalName()
        );

        $path = $file->store('prediksi', 'public');

        return $this->simpan(
            $hasil,
            $path,
            $file->getClientOriginalName(),
            $sumber
        );
    }

    public function dariBerkas(string $absolutePath, string $sumber, ?string $labelDataset = null): Prediksi
    {
        $nama = basename($absolutePath);
        $hasil = $this->ml->prediksiFile($absolutePath, $nama);

        $ext = strtolower(pathinfo($nama, PATHINFO_EXTENSION)) ?: 'jpg';
        $path = 'prediksi/'.Str::uuid().'.'.$ext;
        Storage::disk('public')->put($path, file_get_contents($absolutePath));

        return $this->simpan($hasil, $path, $nama, $sumber, $labelDataset);
    }

    public function dariBase64(string $base64, string $sumber = 'api'): Prediksi
    {
        if (str_contains($base64, ',')) {
            $base64 = explode(',', $base64, 2)[1];
        }

        $mentah = base64_decode(preg_replace('/\s+/', '', $base64), true);
        if ($mentah === false || $mentah === '') {
            throw new InvalidArgumentException('Data base64 tidak valid.');
        }

        if (strlen($mentah) > 10 * 1024 * 1024) {
            throw new InvalidArgumentException('Ukuran gambar melebihi batas 10 MB.');
        }

        $info = @getimagesizefromstring($mentah);
        if ($info === false) {
            throw new InvalidArgumentException('Data base64 bukan gambar yang valid.');
        }

        $hasil = $this->ml->prediksiBase64(base64_encode($mentah));

        $mimeExt = match ($info['mime'] ?? '') {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $path = 'prediksi/'.Str::uuid().'.'.$mimeExt;
        Storage::disk('public')->put($path, $mentah);

        return $this->simpan($hasil, $path, 'api-upload.'.$mimeExt, $sumber);
    }

    private function simpan(
        array $hasil,
        string $path,
        ?string $namaAsli,
        string $sumber,
        ?string $labelDataset = null
    ): Prediksi {
        $probabilitas = $hasil['probabilitas'] ?? [];
        $label = (string) $hasil['prediksi'];

        return Prediksi::create([
            'gambar' => $path,
            'nama_asli' => $namaAsli,
            'sumber' => $sumber,
            'label' => $label,
            'nama_kelas' => $hasil['nama_kelas']
                ?? config('prediksi.classes')[$label]
                ?? $label,
            'confidence' => (float) ($hasil['kepercayaan'] ?? 0),
            'prob_ada_resi' => (float) ($probabilitas['ada_resi'] ?? 0),
            'prob_bukan_resi' => (float) ($probabilitas['bukan_resi'] ?? 0),
            'prob_tidak_ada_resi' => (float) ($probabilitas['tidak_ada_resi'] ?? 0),
            'waktu_proses_ms' => (int) round(((float) ($hasil['waktu_proses_detik'] ?? 0)) * 1000),
            'jumlah_fitur' => (int) ($hasil['jumlah_fitur'] ?? 1812),
            'label_dataset' => $labelDataset,
            'nomor_resi' => $hasil['nomor_resi'] ?? null,
            'metode_baca' => $hasil['metode_baca'] ?? null,
            'label_ketemu' => isset($hasil['label_ketemu'])
                ? (bool) $hasil['label_ketemu']
                : null,
        ]);
    }
}
