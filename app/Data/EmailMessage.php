<?php

namespace App\Data;

final class EmailMessage
{
    public string $deliveryId = '';

    /**
     * @param array<int, array{email: string, name?: string|null}> $recipients
     * @param array<int, string> $introLines
     * @param array<string, string> $details
     * @param array<int, string> $outroLines
     * @param array<string, scalar|null> $metadata
     */
    public function __construct(
        public string $key,
        public array $recipients,
        public string $subject,
        public string $heading,
        public array $introLines = [],
        public array $details = [],
        public ?string $actionText = null,
        public ?string $actionUrl = null,
        public array $outroLines = [],
        public ?string $replyTo = null,
        public ?string $replyToName = null,
        public array $metadata = [],
    ) {
        // Generated before queue dispatch so retries carry the same ID.
        $this->deliveryId = (string) \Illuminate\Support\Str::uuid();
    }
}
