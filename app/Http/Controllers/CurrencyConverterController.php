<?php

namespace App\Http\Controllers;

use App\Services\CurrencyConverterService;
use App\Http\Requests\ConvertCurrencyRequest;
use Illuminate\Http\JsonResponse;

class CurrencyConverterController extends Controller
{
    protected CurrencyConverterService $converterService;

    public function __construct(CurrencyConverterService $converterService)
    {
        $this->converterService = $converterService;
    }

    public function convert(ConvertCurrencyRequest $request): JsonResponse
    {
        try {
            $result = $this->converterService->convert(
                $request->validated('amount'),
                $request->validated('from_currency'),
                $request->validated('to_currency')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Currency conversion failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getRate(string $fromCurrency, string $toCurrency): JsonResponse
    {
        try {
            $rate = $this->converterService->getExchangeRate($fromCurrency, $toCurrency);

            return response()->json([
                'success' => true,
                'data' => [
                    'from_currency' => strtoupper($fromCurrency),
                    'to_currency' => strtoupper($toCurrency),
                    'exchange_rate' => $rate,
                    'timestamp' => now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch exchange rate',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}