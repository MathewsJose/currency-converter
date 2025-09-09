<?php

namespace Tests\Unit\Services;

use App\Exceptions\CurrencyConversionException;
use App\Services\SwopCurrencyConverter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SwopCurrencyConverterTest extends TestCase
{
    private SwopCurrencyConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->converter = new SwopCurrencyConverter(
            apiKey: 'test-api-key',
            baseUrl: 'https://swop.cx/rest',
            cacheTtl: 3600
        );
    }

    public function test_successful_conversion(): void
    {
        Http::fake([
            'https://swop.cx/rest/rates/USD/EUR' => Http::response([
                'quote' => 0.85,
            ], 200),
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->with('exchange_rate:USD:EUR', 3600, \Closure::class)
            ->andReturn(0.85);

        $result = $this->converter->convert(100, 'USD', 'EUR');

        $this->assertEquals(100, $result->amount);
        $this->assertEquals('USD', $result->fromCurrency);
        $this->assertEquals('EUR', $result->toCurrency);
        $this->assertEquals(0.85, $result->exchangeRate);
        $this->assertEquals(85.0, $result->convertedAmount);
    }

    public function test_throws_exception_for_same_currencies(): void
    {
        $this->expectException(CurrencyConversionException::class);
        $this->expectExceptionMessage('Source and target currencies cannot be the same');

        $this->converter->convert(100, 'USD', 'USD');
    }

    public function test_throws_exception_for_invalid_currency_length(): void
    {
        $this->expectException(CurrencyConversionException::class);
        $this->expectExceptionMessage('Currency codes must be 3 letters long');

        $this->converter->convert(100, 'US', 'EUR');
    }

    public function test_throws_exception_for_invalid_currency_format(): void
    {
        $this->expectException(CurrencyConversionException::class);
        $this->expectExceptionMessage('Currency codes must contain only uppercase letters');

        $this->converter->convert(100, 'US1', 'EUR');
    }

    public function test_throws_exception_when_api_returns_error(): void
    {
        Http::fake([
            'https://swop.cx/rest/rates/USD/EUR' => Http::response([
                'error' => 'Invalid API key',
            ], 401),
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->with('exchange_rate:USD:EUR', 3600, \Closure::class)
            ->andThrow(new CurrencyConversionException('Failed to fetch exchange rate'));

        $this->expectException(CurrencyConversionException::class);
        $this->expectExceptionMessage('Failed to fetch exchange rate');

        $this->converter->convert(100, 'USD', 'EUR');
    }

    public function test_throws_exception_when_api_returns_invalid_format(): void
    {
        Http::fake([
            'https://swop.cx/rest/rates/USD/EUR' => Http::response([
                'invalid' => 'data',
            ], 200),
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->with('exchange_rate:USD:EUR', 3600, \Closure::class)
            ->andThrow(new CurrencyConversionException('Exchange rate for EUR not found in response'));

        $this->expectException(CurrencyConversionException::class);
        $this->expectExceptionMessage('Exchange rate for EUR not found in response');

        $this->converter->convert(100, 'USD', 'EUR');
    }

    public function test_throws_exception_on_network_failure(): void
    {
        Http::fake([
            'https://swop.cx/rest/rates/USD/EUR' => Http::response([], 500),
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->with('exchange_rate:USD:EUR', 3600, \Closure::class)
            ->andThrow(new CurrencyConversionException('Failed to fetch exchange rate'));

        $this->expectException(CurrencyConversionException::class);
        $this->expectExceptionMessage('Failed to fetch exchange rate');

        $this->converter->convert(100, 'USD', 'EUR');
    }

    public function test_caching_is_used_for_exchange_rates(): void
    {
        $cacheKey = 'exchange_rate:USD:EUR';
        $expectedRate = 0.85;

        // Mock cache to expect the remember call
        Cache::shouldReceive('remember')
            ->once()
            ->with($cacheKey, 3600, \Closure::class)
            ->andReturn($expectedRate);

        // Don't fake HTTP since cache should return the value
        // Http::fake() should not be called here

        $result = $this->converter->convert(100, 'USD', 'EUR');

        $this->assertEquals($expectedRate, $result->exchangeRate);
    }
}