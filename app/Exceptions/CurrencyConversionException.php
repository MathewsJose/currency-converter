<?php

namespace App\Exceptions;

use Exception;

class CurrencyConversionException extends Exception
{
    public function __construct(
        string $message = "Currency conversion failed",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}