<?php

use App\Http\Controllers\CurrencyConverterController;
use App\Services\InfluxDBService;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'OK']);
});

Route::post('/convert', [CurrencyConverterController::class, 'convert'])
    ->name('currency.convert');

// Route::get('/test-influx-write', function () {
//     try {
//         $service = app(InfluxDBService::class);
        
//         if (!$service->isEnabled()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'InfluxDBService is disabled'
//             ]);
//         }

//         $point = $service->createPoint('test_measurement')
//             ->addTag('service', 'test')
//             ->addTag('environment', app()->environment())
//             ->addField('value', 1.0)
//             ->addField('success', true);

//         $service->writePoint($point);

//         return response()->json([
//             'success' => true,
//             'message' => 'Test point written successfully',
//             'point_data' => $point->toLineProtocol()
//         ]);

//     } catch (\Exception $e) {
//         return response()->json([
//             'success' => false,
//             'error' => $e->getMessage(),
//             'trace' => $e->getTraceAsString()
//         ], 500);
//     }
// });