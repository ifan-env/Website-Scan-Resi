<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::get('/model', [ApiController::class, 'model'])->name('api.model');
Route::get('/health', [ApiController::class, 'health'])->name('api.health');
Route::post('/predict', [ApiController::class, 'predict'])->name('api.predict');
