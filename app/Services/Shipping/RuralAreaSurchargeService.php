<?php

namespace App\Services\Shipping;

use App\Models\RuralAreaSurcharge;
use Illuminate\Support\Str;

class RuralAreaSurchargeService
{
    public function resolve(?string $postalCode, ?string $country = 'United States', ?string $state = null): ?array
    {
        $postalCode = $this->normalize((string) $postalCode);

        if ($postalCode === '') {
            return null;
        }

        $country = trim((string) ($country ?: 'United States'));
        $state = trim((string) $state);

        $rules = RuralAreaSurcharge::query()
            ->where('is_active', true)
            ->where(function ($query) use ($country): void {
                $query->where('country', $country)->orWhereNull('country')->orWhere('country', '');
            })
            ->where(function ($query) use ($state): void {
                $query->whereNull('state')->orWhere('state', '');

                if ($state !== '') {
                    $query->orWhere('state', $state);
                }
            })
            ->orderByDesc('amount')
            ->get();

        foreach ($rules as $rule) {
            foreach ($rule->patternList() as $pattern) {
                if ($this->matches($postalCode, $pattern)) {
                    return [
                        'id' => $rule->id,
                        'name' => $rule->name,
                        'amount' => round((float) $rule->amount, 2),
                        'postal_code' => $postalCode,
                        'matched_pattern' => $pattern,
                        'message' => 'Rural area surcharge applied for ZIP/postal code '.$postalCode.'.',
                    ];
                }
            }
        }

        return null;
    }

    private function matches(string $postalCode, string $pattern): bool
    {
        $normalizedPattern = $this->normalize($pattern);

        if ($normalizedPattern === '') {
            return false;
        }

        if (str_contains($normalizedPattern, '-')) {
            [$start, $end] = array_pad(explode('-', $normalizedPattern, 2), 2, null);
            $start = preg_replace('/\D/', '', (string) $start);
            $end = preg_replace('/\D/', '', (string) $end);
            $zip = preg_replace('/\D/', '', $postalCode);

            if ($start !== '' && $end !== '' && $zip !== '') {
                return (int) $zip >= (int) $start && (int) $zip <= (int) $end;
            }
        }

        if (str_contains($normalizedPattern, '*')) {
            $regex = '/^'.str_replace('\\*', '.*', preg_quote($normalizedPattern, '/')).'$/i';
            return (bool) preg_match($regex, $postalCode);
        }

        return Str::upper($postalCode) === Str::upper($normalizedPattern);
    }

    private function normalize(string $value): string
    {
        return Str::upper(trim(preg_replace('/\s+/', '', $value) ?? ''));
    }
}
