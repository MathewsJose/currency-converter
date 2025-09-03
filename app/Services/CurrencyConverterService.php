<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CurrencyConverterService
{
    protected string $baseUrl;
    protected int $cacheTtl;

    public function __construct()
    {
        $this->baseUrl = config('services.swop.base_url', 'https://swop.cx/rest');
        $this->cacheTtl = config('services.swop.cache_ttl', 3600);
    }

    public function convert(float $amount, string $fromCurrency, string $toCurrency): array
    {
        $this->validateInput($amount, $fromCurrency, $toCurrency);

        $exchangeRate = $this->getExchangeRate($fromCurrency, $toCurrency);
        $convertedAmount = $amount * $exchangeRate;

        return [
            'original_amount' => $amount,
            'original_currency' => strtoupper($fromCurrency),
            'converted_amount' => $convertedAmount,
            'target_currency' => strtoupper($toCurrency),
            'exchange_rate' => $exchangeRate,
            'formatted_original' => $this->formatCurrency($amount, $fromCurrency),
            'formatted_converted' => $this->formatCurrency($convertedAmount, $toCurrency),
            'timestamp' => now()->toISOString(),
        ];
    }

    public function getExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);

        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $cacheKey = "exchange_rate:{$fromCurrency}:{$toCurrency}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($fromCurrency, $toCurrency) {
            return $this->fetchExchangeRateFromApi($fromCurrency, $toCurrency);
        });
    }

    protected function fetchExchangeRateFromApi(string $fromCurrency, string $toCurrency): float
    {
        try {
            $response = Http::timeout(10)
                ->retry(3, 100)
                ->get("{$this->baseUrl}/rates/{$fromCurrency}/{$toCurrency}");

            if ($response->failed()) {
                Log::error('SWOP API request failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new \Exception("Failed to fetch exchange rate from SWOP API. Status: {$response->status()}");
            }

            $data = $response->json();

            if (!isset($data['rate'])) {
                throw new \Exception('Invalid response format from SWOP API');
            }

            return (float) $data['rate'];

        } catch (\Exception $e) {
            Log::error('Error fetching exchange rate from SWOP API', [
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function validateInput(float $amount, string $fromCurrency, string $toCurrency): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than 0');
        }

        if (!preg_match('/^[A-Z]{3}$/i', $fromCurrency)) {
            throw new InvalidArgumentException('Invalid from currency format. Must be 3-letter currency code.');
        }

        if (!preg_match('/^[A-Z]{3}$/i', $toCurrency)) {
            throw new InvalidArgumentException('Invalid to currency format. Must be 3-letter currency code.');
        }
    }

    protected function formatCurrency(float $amount, string $currency): string
    {
        $currency = strtoupper($currency);
        
        $formatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
        $formatter->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $currency);
        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);

        return $formatter->formatCurrency($amount, $currency);
    }
}