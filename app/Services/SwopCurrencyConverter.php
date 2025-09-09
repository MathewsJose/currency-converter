<?php

namespace App\Services;

use App\Services\Contracts\CurrencyConverterInterface;
use App\DataTransferObjects\CurrencyConversionResult;
use App\Exceptions\CurrencyConversionException;
use App\Services\SimpleInfluxService;
use App\Services\InfluxDBService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SwopCurrencyConverter implements CurrencyConverterInterface
{
    public function __construct(
        private string $apiKey,
        private string $baseUrl,
        private int $cacheTtl = 3600, // 1 hour        
        private ?InfluxDBService $influxDBService = null 
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
            convertedAmount: round($convertedAmount, 2, PHP_ROUND_HALF_UP)
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
        $startTime = microtime(true);
        try {            
            $response = Http::timeout(10)
                ->retry(3, 100)
                ->withHeaders([
                    'Authorization' => 'ApiKey '. $this->apiKey,
                ])
                ->get("{$this->baseUrl}/rates/{$fromCurrency}/{$toCurrency}");
            $duration = microtime(true) - $startTime;
            if ($response->failed()) {
                $this->influxDBService->logApiMetrics([
                    'from_currency' => $fromCurrency,
                    'to_currency' => $toCurrency,
                    'duration_ms' => $duration * 1000,
                    'status' => 'failed',
                    'status_code' => $response->status(),
                    'error' => $response->body(),
                ]);

                throw new CurrencyConversionException(
                    "Failed to fetch exchange rate: {$response->body()}",
                    $response->status()
                );
            }
            $data = $response->json();
            if (!isset($data['quote'])) {
                $this->influxDBService->logApiMetrics([
                    'from_currency' => $fromCurrency,
                    'to_currency' => $toCurrency,
                    'duration_ms' => $duration * 1000,
                    'status' => 'failed',
                    'error' => 'Exchange rate not found in response',
                ]);

                throw new CurrencyConversionException("Exchange rate for {$toCurrency} not found in response");
            }

            $this->logApiMetrics([
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'duration_ms' => $duration * 1000,
                'status' => 'success',
                'exchange_rate' => (float) $data['quote'],
            ]);
            return (float) $data['quote'];
        } catch (Throwable $e) {
            $duration = microtime(true) - $startTime;            
            $this->logApiMetrics([
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'duration_ms' => $duration * 1000,
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);

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

    private function logApiMetrics(array $data): void
    {
        try {
      
            $this->influxDBService->writeMeasurement(
                'exchange_rate_api',
                [
                    'from_currency' => $data['from_currency'],
                    'to_currency' => $data['to_currency'],
                    'status' => $data['status']
                ],
                [
                    'duration_ms' => (float)$data['duration_ms'],
                    'exchange_rate' => isset($data['exchange_rate']) ? (float)$data['exchange_rate'] : 0,
                    'status_code' => isset($data['status_code']) ? (int)$data['status_code'] : 0,
                    'error' => $data['error'] ?? ''
                ]
            );

        } catch (\Exception $e) {
            Log::warning('Failed to log API metrics to InfluxDB', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}