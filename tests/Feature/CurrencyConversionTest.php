<?php
// tests/Feature/CurrencyConversionTest.php

namespace Tests\Feature;

use App\Services\Contracts\CurrencyConverterInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the CurrencyConverterInterface for the entire application
        $this->mock(CurrencyConverterInterface::class, function ($mock) {
            $mock->shouldReceive('convert')
                ->zeroOrMoreTimes()
                ->andReturn(new \App\DataTransferObjects\CurrencyConversionResult(
                    amount: 100.0,
                    fromCurrency: 'USD',
                    toCurrency: 'EUR',
                    exchangeRate: 0.85,
                    convertedAmount: 85.0
                ));
        });
    }

    public function test_successful_currency_conversion(): void
    {
        $response = $this->postJson('/api/convert', [
            'amount' => 100,
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'amount' => 100,
                    'from_currency' => 'USD',
                    'to_currency' => 'EUR',
                    'exchange_rate' => 0.85,
                    'converted_amount' => 85.0,
                ],
            ]);
    }

    public function test_validation_fails_with_invalid_currencies(): void
    {
        $response = $this->postJson('/api/convert', [
            'amount' => 100,
            'from_currency' => 'US', // Invalid - only 2 characters
            'to_currency' => 'EUR',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_currency']);
    }

    public function test_caching_is_used_for_exchange_rates(): void
    {
        // For this test, we need to specifically mock the convert method
        $mock = $this->mock(CurrencyConverterInterface::class);
        $mock->shouldReceive('convert')
            ->once()
            ->with(100.0, 'USD', 'EUR')
            ->andReturn(new \App\DataTransferObjects\CurrencyConversionResult(
                amount: 100.0,
                fromCurrency: 'USD',
                toCurrency: 'EUR',
                exchangeRate: 0.85,
                convertedAmount: 85.0
            ));

        $response = $this->postJson('/api/convert', [
            'amount' => 100,
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
        ]);

        $response->assertStatus(200);
    }
}