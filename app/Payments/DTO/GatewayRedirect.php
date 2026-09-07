<?php

namespace App\Payments\DTO;

final readonly class GatewayRedirect
{
    public function __construct(
        public ?string $url,
        public ?string $providerReference = null,
        public ?string $providerSessionId = null,
        public ?string $providerPaymentId = null,
        public array $metadata = [],
    ) {
    }

    public function requiresRedirect(): bool
    {
        return filled($this->url);
    }
}
