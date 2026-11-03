<?php

namespace App\Http\Controllers;

use App\Models\Prediksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        $filter = [
            'label' => $request->query('label'),
            'sumber' => $request->query('sumber'),
        ];

        if (! array_key_exists((string) $filter['label'], config('prediksi.classes'))) {
            $filter['label'] = null;
        }

        if (! in_array($filter['sumber'], ['upload', 'kamera', 'contoh', 'api'], true)) {
            $filter['sumber'] = null;
        }

        $riwayat = Prediksi::filter($filter)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => Prediksi::count(),
            'ada_resi' => Prediksi::where('label', 'ada_resi')->count(),
            'akurasi_rata' => round(((float) Prediksi::avg('confidence')) * 100, 1),
        ];

        return view('riwayat', [
            'riwayat' => $riwayat,
            'filter' => $filter,
            'stats' => $stats,
        ]);
    }

    public function destroy(Prediksi $prediksi)
    {
        if ($prediksi->gambar) {
            Storage::disk('public')->delete($prediksi->gambar);
        }

        $prediksi->delete();

        return redirect()
            ->route('riwayat')
            ->with('sukses', 'Data prediksi berhasil dihapus.');
    }
}
