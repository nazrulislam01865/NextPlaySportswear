<?php

namespace App\Payments\DTO;

final readonly class GatewayRefundResult
{
    public function __construct(
        public string $status,
        public ?string $providerReference = null,
        public array $metadata = [],
    ) {
    }
}
