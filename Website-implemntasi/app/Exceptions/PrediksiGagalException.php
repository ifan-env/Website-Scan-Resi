<?php

namespace App\Exceptions;

use RuntimeException;

class PrediksiGagalException extends RuntimeException
{
    public static function serviceMati(): self
    {
        return new self(
            'Service model Python tidak dapat dihubungi. Jalankan '
            .'`python ml_service/app.py` lalu coba lagi.'
        );
    }
}
