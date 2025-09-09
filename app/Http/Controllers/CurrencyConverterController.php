<?php

namespace App\Http\Controllers;

use App\Services\Contracts\CurrencyConverterInterface;
use App\Services\InfluxDBService;
use App\Http\Requests\ConvertCurrencyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CurrencyConverterController extends Controller
{

    public function __construct(
        private CurrencyConverterInterface $currencyConverter
    ) {}

    public function convert(ConvertCurrencyRequest $request): JsonResponse
    {
        $startTime = microtime(true);
        try {
            $result = $this->currencyConverter->convert(
                amount: $request->validated('amount'),
                fromCurrency: $request->validated('from_currency'),
                toCurrency: $request->validated('to_currency')
            );

            $duration = microtime(true) - $startTime;
            $this->logConversionMetrics([
                'amount' => $result->amount,
                'from_currency' => $result->fromCurrency,
                'to_currency' => $result->toCurrency,
                'exchange_rate' => $result->exchangeRate,
                'converted_amount' => $result->convertedAmount,
                'duration_ms' => $duration * 1000,
                'status' => 'success',
            ]);

            return response()->json([
                'success' => true,
                'data' => $result->toArray(),
            ]);

        } catch (\App\Exceptions\CurrencyConversionException $e) {

            $duration = microtime(true) - $startTime;                        
            $this->logConversionMetrics([
                'amount' => $request->validated('amount'),
                'from_currency' => $request->validated('from_currency'),
                'to_currency' => $request->validated('to_currency'),
                'duration_ms' => $duration * 1000,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {

            $duration = microtime(true) - $startTime;            
            $this->logConversionMetrics([
                'amount' => $request->validated('amount'),
                'from_currency' => $request->validated('from_currency'),
                'to_currency' => $request->validated('to_currency'),
                'duration_ms' => $duration * 1000,
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);

            Log::error('Unexpected error in currency conversion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }

    private function logConversionMetrics(array $data): void
    {
        try {
            $influxService = app(SimpleInfluxService::class);
            
            $influxService->writeMeasurement(
                'currency_conversion',
                [
                    'from_currency' => $data['from_currency'],
                    'to_currency' => $data['to_currency'],
                    'status' => $data['status']
                ],
                [
                    'amount' => (float)$data['amount'],
                    'duration_ms' => (float)$data['duration_ms'],
                    'exchange_rate' => isset($data['exchange_rate']) ? (float)$data['exchange_rate'] : 0,
                    'converted_amount' => isset($data['converted_amount']) ? (float)$data['converted_amount'] : 0,
                    'error' => $data['error'] ?? ''
                ]
            );

        } catch (\Exception $e) {
            Log::warning('Failed to log metrics to InfluxDB', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}