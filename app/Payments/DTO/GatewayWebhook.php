<?php

namespace App\Payments\DTO;

final readonly class GatewayWebhook
{
    public function __construct(
        public string $providerEventId,
        public string $type,
        public array $payload,
    ) {
    }
}
