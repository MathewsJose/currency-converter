<?php

use App\Http\Controllers\CurrencyConverterController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::get('/health', function () {
        return response()->json(['status' => 'OK']);
    });

    Route::get('/rate/{fromCurrency}/{toCurrency}', [CurrencyConverterController::class, 'getRate']);
    
    Route::post('/convert', [CurrencyConverterController::class, 'convert']);
});