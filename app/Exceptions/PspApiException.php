<?php

namespace App\Exceptions;

use RuntimeException;

class PspApiException extends RuntimeException
{
    public function __construct(
        public readonly string $pspType,
        string $message,
        public readonly ?array $responseData = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct("[{$pspType}] {$message}", $code, $previous);
    }
}
