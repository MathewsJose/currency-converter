<?php

use App\Http\Controllers\CurrencyConverterController;
use App\Services\InfluxDBService;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'OK']);
});

Route::post('/convert', [CurrencyConverterController::class, 'convert'])
    ->name('currency.convert');