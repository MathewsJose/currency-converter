<?php

namespace App\Http\Controllers;

use App\Services\Contracts\CurrencyConverterInterface;
use App\Http\Requests\ConvertCurrencyRequest;
use Illuminate\Http\JsonResponse;

class CurrencyConverterController extends Controller
{

    public function __construct(
        private CurrencyConverterInterface $currencyConverter
    ) {}

    public function convert(ConvertCurrencyRequest $request): JsonResponse
    {
        try {
            $result = $this->currencyConverter->convert(
                amount: $request->validated('amount'),
                fromCurrency: $request->validated('from_currency'),
                toCurrency: $request->validated('to_currency')
            );

            return response()->json([
                'success' => true,
                'data' => $result->toArray(),
            ]);

        } catch (\App\Exceptions\CurrencyConversionException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
            ], 500);
        }
    }
}