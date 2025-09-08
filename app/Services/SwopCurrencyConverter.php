<?php

namespace App\Services;

use App\Services\Contracts\CurrencyConverterInterface;
use App\DataTransferObjects\CurrencyConversionResult;
use App\Exceptions\CurrencyConversionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SwopCurrencyConverter implements CurrencyConverterInterface
{
    public function __construct(
        private string $apiKey,
        private string $baseUrl,
        private int $cacheTtl = 3600 // 1 hour
    ) {}

    public function convert(
        float $amount,
        string $fromCurrency,
        string $toCurrency
    ): CurrencyConversionResult {
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);

        $this->validateCurrencies($fromCurrency, $toCurrency);

        $exchangeRate = $this->getExchangeRate($fromCurrency, $toCurrency);
        $convertedAmount = $amount * $exchangeRate;

        return new CurrencyConversionResult(
            amount: $amount,
            fromCurrency: $fromCurrency,
            toCurrency: $toCurrency,
            exchangeRate: $exchangeRate,
            convertedAmount: $convertedAmount
        );
    }

    private function getExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        $cacheKey = "exchange_rate:{$fromCurrency}:{$toCurrency}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($fromCurrency, $toCurrency) {
            return $this->fetchExchangeRateFromApi($fromCurrency, $toCurrency);
        });
    }

    private function fetchExchangeRateFromApi(string $fromCurrency, string $toCurrency): float
    {
        try {            
            $response = Http::timeout(10)
                ->retry(3, 100)
                ->withHeaders([
                    'Authorization' => 'ApiKey '. $this->apiKey,
                ])
                ->get("{$this->baseUrl}/rates/{$fromCurrency}/{$toCurrency}");

            if ($response->failed()) {
                throw new CurrencyConversionException(
                    "Failed to fetch exchange rate: {$response->body()}",
                    $response->status()
                );
            }

            $data = $response->json();


            if (!isset($data['quote'])) {
                throw new CurrencyConversionException("Exchange rate for {$toCurrency} not found in response");
            }

            return (float) $data['quote'];

        } catch (Throwable $e) {
            Log::error('Failed to fetch exchange rate from Swop API', [
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'error' => $e->getMessage(),
            ]);

            throw new CurrencyConversionException(
                "Failed to fetch exchange rate: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    private function validateCurrencies(string $fromCurrency, string $toCurrency): void
    {
        if ($fromCurrency === $toCurrency) {
            throw new CurrencyConversionException('Source and target currencies cannot be the same');
        }

        if (strlen($fromCurrency) !== 3 || strlen($toCurrency) !== 3) {
            throw new CurrencyConversionException('Currency codes must be 3 letters long');
        }

        if (!preg_match('/^[A-Z]{3}$/', $fromCurrency) || !preg_match('/^[A-Z]{3}$/', $toCurrency)) {
            throw new CurrencyConversionException('Currency codes must contain only uppercase letters');
        }
    }
}