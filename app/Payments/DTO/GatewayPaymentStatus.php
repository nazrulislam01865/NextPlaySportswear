<?php

namespace App\Payments\DTO;

final readonly class GatewayPaymentStatus
{
    public function __construct(
        public string $status,
        public ?string $providerReference = null,
        public ?string $providerSessionId = null,
        public ?string $providerPaymentId = null,
        public ?int $amountMinor = null,
        public ?string $currency = null,
        public array $metadata = [],
    ) {
    }
}
