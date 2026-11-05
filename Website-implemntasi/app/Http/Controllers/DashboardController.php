<?php

namespace App\Http\Controllers;

use App\Models\Prediksi;
use App\Services\ContohProvider;
use App\Services\InfoModel;
use App\Services\PythonPrediksiService;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __invoke(
        ContohProvider $contoh,
        InfoModel $infoModel,
        PythonPrediksiService $ml
    ) {
        $stats = [
            'total' => Prediksi::count(),
            'ada_resi' => Prediksi::where('label', 'ada_resi')->count(),
            'bukan_resi' => Prediksi::where('label', 'bukan_resi')->count(),
            'tidak_ada_resi' => Prediksi::where('label', 'tidak_ada_resi')->count(),
            'rata_confidence' => round(((float) Prediksi::avg('confidence')) * 100, 1),
        ];

        $terbaru = Prediksi::latest()->take(6)->get();

        $serviceAktif = Cache::remember(
            'ml_service_sehat',
            15,
            fn () => $ml->sehat()
        );

        return view('dashboard', [
            'stats' => $stats,
            'terbaru' => $terbaru,
            'contoh' => $contoh->semua(),
            'metrik' => $infoModel->metrik(),
            'akurasiPersen' => $infoModel->akurasiPersen(),
            'serviceAktif' => $serviceAktif,
        ]);
    }
}
