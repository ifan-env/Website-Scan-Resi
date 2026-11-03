<?php

namespace App\Services;

/**
 * Menyediakan contoh gambar dari dataset latih untuk demo di dashboard.
 */
class ContohProvider
{
    private ?array $daftar = null;

    public function semua(int $perKelas = 4): array
    {
        if ($this->daftar !== null) {
            return $this->daftar;
        }

        $akar = config('prediksi.dataset_path');
        $hasil = [];

        foreach (array_keys(config('prediksi.classes')) as $label) {
            $folder = rtrim($akar, '/\\').DIRECTORY_SEPARATOR.$label;
            if (! is_dir($folder)) {
                continue;
            }

            $berkas = array_values(array_filter(
                scandir($folder),
                fn ($nama) => is_file($folder.DIRECTORY_SEPARATOR.$nama)
                    && in_array(strtolower(pathinfo($nama, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true)
            ));
            sort($berkas);

            $jumlah = min($perKelas, count($berkas));
            for ($i = 0; $i < $jumlah; $i++) {
                $indeks = (int) floor($i * count($berkas) / max($jumlah, 1));
                $nama = $berkas[$indeks] ?? null;
                if ($nama === null) {
                    continue;
                }

                $hasil[] = [
                    'label' => $label,
                    'nama' => $nama,
                    'url' => route('contoh.show', ['label' => $label, 'nama' => $nama]),
                ];
            }
        }

        return $this->daftar = $hasil;
    }

    public function cari(string $label, string $nama): ?array
    {
        return collect($this->semua())
            ->first(fn ($item) => $item['label'] === $label && $item['nama'] === $nama);
    }

    public function path(array $contoh): string
    {
        return rtrim(config('prediksi.dataset_path'), '/\\')
            .DIRECTORY_SEPARATOR.$contoh['label']
            .DIRECTORY_SEPARATOR.$contoh['nama'];
    }

    public function ekstensi(array $contoh): string
    {
        $ext = strtolower(pathinfo($contoh['nama'], PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png'], true) ? $ext : 'jpg';
    }
}
