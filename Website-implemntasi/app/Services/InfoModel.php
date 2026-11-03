<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Throwable;

class InfoModel
{
    /**
     * Metrik evaluasi dari laporan training (jika tersedia).
     */
    public function metrik(): array
    {
        return Cache::remember('metrik_model', 300, function () {
            $metrik = [
                'akurasi_latih' => null,
                'akurasi_uji' => null,
                'jumlah_data' => null,
            ];

            $laporan = base_path('../projek-semester5/hasil_latih_folder.txt');

            try {
                if (! is_file($laporan)) {
                    return $metrik;
                }

                foreach (file($laporan, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $baris) {
                    if (str_starts_with($baris, 'Akurasi latih:')) {
                        $metrik['akurasi_latih'] = trim(explode(':', $baris, 2)[1]);
                    } elseif (str_starts_with($baris, 'Akurasi uji')) {
                        $metrik['akurasi_uji'] = trim(explode(':', $baris, 2)[1]);
                    } elseif (str_starts_with($baris, 'Dataset:')) {
                        if (preg_match('/\((\d+)\s*gambar/', $baris, $cocok)) {
                            $metrik['jumlah_data'] = (int) $cocok[1];
                        }
                    }
                }
            } catch (Throwable) {
                // biarkan metrik kosong bila laporan tidak bisa dibaca
            }

            return $metrik;
        });
    }

    public function akurasiPersen(): ?float
    {
        $uji = $this->metrik()['akurasi_uji'];

        return $uji !== null ? round((float) $uji * 100, 1) : null;
    }
}
