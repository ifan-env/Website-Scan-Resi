<?php

use App\Http\Controllers\ContohController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PrediksiController;
use App\Http\Controllers\RiwayatController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');

Route::post('/prediksi', [PrediksiController::class, 'store'])->name('prediksi.store');
Route::post('/prediksi/contoh', [PrediksiController::class, 'contoh'])->name('prediksi.contoh');

Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat');
Route::delete('/riwayat/{prediksi}', [RiwayatController::class, 'destroy'])->name('riwayat.destroy');

Route::get('/dokumentasi-api', fn () => view('api'))->name('api.docs');

Route::get('/contoh/{label}/{nama}', ContohController::class)
    ->where('nama', '[^/]+')
    ->name('contoh.show');
