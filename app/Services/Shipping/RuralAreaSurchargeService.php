<?php

namespace App\Services\Shipping;

use App\Models\RuralAreaSurcharge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class RuralAreaSurchargeService
{
    public function __construct(
        private readonly RemoteAreaSurchargeNormalizer $normalizer,
    ) {
    }

    public function resolve(
        ?string $postalCode,
        ?string $country = 'United States',
        ?string $state = null,
        ?string $city = null,
    ): ?array {
        $postalCode = $this->normalizer->normalizePostal((string) $postalCode);
        $cityNormalized = $this->normalizer->normalizeCity((string) $city);

        if ($postalCode === '' && $cityNormalized === null) {
            return null;
        }

        $country = trim((string) ($country ?: 'United States'));
        $state = trim((string) $state);

        if ($structured = $this->resolveStructured($postalCode, $country, $cityNormalized)) {
            return $structured;
        }

        // Keep the previous pattern-based engine as a fallback for existing manual records.
        // Imported UPS records have iata_code populated, so they are excluded from this path.
        $rules = RuralAreaSurcharge::query()
            ->where('is_active', true)
            ->whereNull('iata_code')
            ->where(function ($query) use ($country): void {
                $query->where('country', $country)->orWhereNull('country')->orWhere('country', '');
            })
            ->where(function ($query) use ($state): void {
                $query->whereNull('state')->orWhere('state', '');

                if ($state !== '') {
                    $query->orWhere('state', $state);
                }
            })
            ->orderByDesc('extra_charge')
            ->orderByDesc('amount')
            ->get();

        foreach ($rules as $rule) {
            foreach ($rule->patternList() as $pattern) {
                if ($this->matchesLegacyPattern($postalCode, $pattern)) {
                    return $this->result($rule, $postalCode, $pattern);
                }
            }
        }

        return null;
    }

    private function resolveStructured(string $postalCode, string $country, ?string $cityNormalized): ?array
    {
        $base = RuralAreaSurcharge::query()
            ->where('is_active', true)
            ->whereNotNull('iata_code')
            ->where(function (Builder $query): void {
                $query->whereNull('destination_surcharge')
                    ->orWhere('destination_surcharge', '<>', 'No');
            })
            ->where(function (Builder $query) use ($country): void {
                $query->where('country', $country);

                $countryCode = Str::upper(trim($country));
                if (preg_match('/^[A-Z]{2}$/', $countryCode)) {
                    $query->orWhere('iata_code', $countryCode);
                }
            });

        // Some UPS countries are defined by city instead of postal code (Low/High = 0).
        // Exact normalized city matching is indexed and should win before a postal range.
        if ($cityNormalized !== null) {
            $cityRule = (clone $base)
                ->where('city_normalized', $cityNormalized)
                ->orderByDesc('extra_charge')
                ->first();

            if ($cityRule) {
                return $this->result($cityRule, $postalCode, 'City: '.$cityRule->city);
            }
        }

        if ($postalCode === '') {
            return null;
        }

        if (ctype_digit($postalCode)) {
            $numericPostal = (int) $postalCode;
            $numericRule = (clone $base)
                ->whereNull('city_normalized')
                ->whereNotNull('postal_code_low_numeric')
                ->whereNotNull('postal_code_high_numeric')
                ->where('postal_code_low_numeric', '<=', $numericPostal)
                ->where('postal_code_high_numeric', '>=', $numericPostal)
                ->orderByRaw('(postal_code_high_numeric - postal_code_low_numeric) ASC')
                ->orderByDesc('extra_charge')
                ->first();

            if ($numericRule) {
                return $this->result($numericRule, $postalCode, $numericRule->postalRangeLabel());
            }
        }

        // Alphanumeric postal systems (Canada/UK/etc.) need natural/prefix range matching.
        // Their country datasets are comparatively small, while numeric-heavy countries use
        // the indexed query above. This avoids loading the 65k-row master list into memory.
        $alphaRules = (clone $base)
            ->whereNull('city_normalized')
            ->whereNull('postal_code_low_numeric')
            ->orderByRaw('CASE WHEN LENGTH(postal_code_low_normalized) > LENGTH(postal_code_high_normalized) THEN LENGTH(postal_code_low_normalized) ELSE LENGTH(postal_code_high_normalized) END DESC')
            ->orderByDesc('extra_charge')
            ->get();

        foreach ($alphaRules as $rule) {
            if ($this->matchesStructuredRange($postalCode, $rule)) {
                return $this->result($rule, $postalCode, $rule->postalRangeLabel());
            }
        }

        return null;
    }

    private function matchesStructuredRange(string $postalCode, RuralAreaSurcharge $rule): bool
    {
        $low = (string) $rule->postal_code_low_normalized;
        $high = (string) $rule->postal_code_high_normalized;

        if ($low === '' || $high === '') {
            return false;
        }

        if ($low === $high) {
            // UPS sometimes stores only a postcode prefix (for example GB "AB37" or "IM").
            return Str::startsWith($postalCode, $low);
        }

        $minimumLength = min(strlen($low), strlen($high));
        $maximumLength = max(strlen($low), strlen($high));

        // Range endpoints can have different prefix lengths (for example GB IV4-IV11).
        // Try each source prefix width so the customer's inward-code characters are never
        // mistaken for part of the UPS outward-area range.
        for ($length = $minimumLength; $length <= $maximumLength; $length++) {
            $candidate = substr($postalCode, 0, $length);

            if (strnatcasecmp($candidate, $low) >= 0 && strnatcasecmp($candidate, $high) <= 0) {
                return true;
            }
        }

        return false;
    }

    private function matchesLegacyPattern(string $postalCode, string $pattern): bool
    {
        // Preserve '-' and '*' while normalizing legacy syntax. Structured postal fields
        // never rely on this parser, but old manual ranges and wildcards must keep working.
        $normalizedPattern = Str::upper(trim(preg_replace('/\s+/u', '', $pattern) ?? ''));

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

    private function result(RuralAreaSurcharge $rule, string $postalCode, string $matchedPattern): array
    {
        return [
            'id' => $rule->id,
            'name' => $rule->name,
            'carrier' => $rule->carrier ?: 'UPS',
            'amount' => $rule->effectiveExtraCharge(),
            'extra_charge' => $rule->effectiveExtraCharge(),
            'postal_code' => $postalCode,
            'city' => $rule->city,
            'matched_pattern' => $matchedPattern,
            'origin_surcharge' => $rule->origin_surcharge,
            'destination_surcharge' => $rule->destination_surcharge,
            'message' => 'Remote area surcharge applied for '.$matchedPattern.'.',
        ];
    }
}
