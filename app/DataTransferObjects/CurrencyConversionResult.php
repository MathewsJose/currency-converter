<?php

namespace App\DataTransferObjects;

use NumberFormatter;
use Illuminate\Contracts\Support\Arrayable;

class CurrencyConversionResult implements Arrayable
{
    public function __construct(
        public readonly float $amount,
        public readonly string $fromCurrency,
        public readonly string $toCurrency,
        public readonly float $exchangeRate,
        public readonly float $convertedAmount
    ) {}

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'from_currency' => $this->fromCurrency,
            'to_currency' => $this->toCurrency,
            'exchange_rate' => $this->exchangeRate,
            'converted_amount' => $this->convertedAmount,
            'formatted_converted_amount' => $this->formatCurrency($this->convertedAmount, $this->toCurrency),
            'formatted_exchange_rate' => number_format($this->exchangeRate, 6),
        ];
    }

    private function formatCurrency(float $amount, string $currencyCode): string
    {
        $formatter = new NumberFormatter(app()->getLocale(), NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($amount, $currencyCode);
    }
}