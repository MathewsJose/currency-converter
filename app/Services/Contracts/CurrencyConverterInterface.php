<?php

namespace App\Services\Contracts;

use App\DataTransferObjects\CurrencyConversionResult;
use App\Exceptions\CurrencyConversionException;

interface CurrencyConverterInterface
{
    /**
     * Convert amount from one currency to another
     *
     * @throws CurrencyConversionException
     */
    public function convert(
        float $amount,
        string $fromCurrency,
        string $toCurrency
    ): CurrencyConversionResult;
}