<?php

namespace App\Services;

use App\Exceptions\PrediksiGagalException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Klien HTTP untuk microservice Flask yang memuat model SVM (.pkl).
 */
class PythonPrediksiService
{
    private string $baseUrl;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('prediksi.ml_url');
        $this->timeout = (int) config('prediksi.ml_timeout', 30);
    }

    public function sehat(): bool
    {
        try {
            return Http::timeout(3)
                ->acceptJson()
                ->get($this->baseUrl.'/health')
                ->successful();
        } catch (Throwable) {
            return false;
        }
    }

    public function info(): ?array
    {
        try {
            $response = Http::timeout(5)->acceptJson()->get($this->baseUrl.'/health');

            return $response->successful() ? $response->json() : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function prediksiFile(string $absolutePath, string $namaFile): array
    {
        $isi = @file_get_contents($absolutePath);
        if ($isi === false) {
            throw new PrediksiGagalException('Gambar tidak dapat dibaca.');
        }

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->attach('file', $isi, $namaFile)
                ->post($this->baseUrl.'/predict');
        } catch (ConnectionException) {
            throw PrediksiGagalException::serviceMati();
        } catch (Throwable $e) {
            throw new PrediksiGagalException('Gagal menghubungi service model: '.$e->getMessage());
        }

        return $this->bacaHasil($response);
    }

    public function prediksiBase64(string $base64): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->post($this->baseUrl.'/predict', ['gambar_base64' => $base64]);
        } catch (ConnectionException) {
            throw PrediksiGagalException::serviceMati();
        } catch (Throwable $e) {
            throw new PrediksiGagalException('Gagal menghubungi service model: '.$e->getMessage());
        }

        return $this->bacaHasil($response);
    }

    private function bacaHasil($response): array
    {
        if ($response->failed()) {
            $pesan = $response->json('error')
                ?? 'Service model mengembalikan status '.$response->status().'.';

            throw new PrediksiGagalException($pesan);
        }

        $data = $response->json();
        if (! is_array($data) || ! isset($data['prediksi'])) {
            throw new PrediksiGagalException('Respons service model tidak valid.');
        }

        return $data;
    }
}
