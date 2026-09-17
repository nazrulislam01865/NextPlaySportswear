<?php

namespace App\Exceptions;

use RuntimeException;

final class CheckoutApiConflictException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly array $context = [],
    ) {
        parent::__construct($message);
    }
}
