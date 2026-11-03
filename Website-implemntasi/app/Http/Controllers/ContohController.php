<?php

namespace App\Http\Controllers;

use App\Services\ContohProvider;

class ContohController extends Controller
{
    public function __invoke(ContohProvider $contoh, string $label, string $nama)
    {
        abort_unless(in_array($label, array_keys(config('prediksi.classes')), true), 404);

        $item = $contoh->cari($label, $nama);
        abort_if($item === null, 404);

        return response()->file($contoh->path($item), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
