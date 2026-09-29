<?php

namespace App\Services\Promotions;

use App\Models\SaleCampaign;
use RuntimeException;

class SaleCampaignCodeGenerator
{
    private const SUFFIX_CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    private const MAX_ATTEMPTS = 100;

    public function generate(string $campaignName): string
    {
        $prefix = $this->prefixFor($campaignName);

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $candidate = $prefix.'-'.$this->randomSuffix();

            if (! SaleCampaign::withTrashed()->where('internal_code', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new RuntimeException('Unable to generate a unique sale campaign internal code.');
    }

    public function prefixFor(string $campaignName): string
    {
        $words = array_values(array_filter(
            preg_split('/[^A-Za-z0-9]+/', trim($campaignName)) ?: [],
            fn (string $word): bool => $word !== ''
        ));

        if (count($words) >= 2) {
            $prefix = implode('', array_map(
                fn (string $word): string => strtoupper(substr($word, 0, 1)),
                array_slice($words, 0, 4)
            ));
        } else {
            $single = preg_replace('/[^A-Za-z0-9]/', '', $words[0] ?? '') ?? '';
            $prefix = strtoupper(substr($single, 0, 2));
        }

        return $prefix !== '' ? $prefix : 'SC';
    }

    protected function randomSuffix(): string
    {
        $suffix = '';
        $maxIndex = strlen(self::SUFFIX_CHARACTERS) - 1;

        for ($i = 0; $i < 4; $i++) {
            $suffix .= self::SUFFIX_CHARACTERS[random_int(0, $maxIndex)];
        }

        return $suffix;
    }
}
