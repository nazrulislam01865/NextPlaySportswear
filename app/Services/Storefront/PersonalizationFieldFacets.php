<?php

namespace App\Services\Storefront;

use App\Models\PersonalizationTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One source of truth for catalog filters based on customer-facing fields in
 * published, active personalization templates assigned to actual products.
 * Template names and section headings never become catalog filter options.
 */
class PersonalizationFieldFacets
{
    /** @var array<string, array<int, int>>|null */
    private ?array $templateIdsByValue = null;

    public static function valueForLabel(string $label): string
    {
        return 'field-'.sha1(self::normalizeLabel($label));
    }

    /** @return array<int, array{value:string,label:string,count:int}> */
    public function options(Collection $products): array
    {
        $products = $products->filter(fn ($product): bool => (bool) $product->jersey_roster_enabled)->values();
        if ($products->isEmpty()) {
            return [];
        }

        $assigned = DB::table('product_personalization_template')
            ->whereIn('product_id', $products->pluck('id')->all())
            ->get(['product_id', 'personalization_template_id'])
            ->groupBy('product_id');

        $ids = $assigned->flatten(1)->pluck('personalization_template_id')
            ->merge($products->pluck('personalization_template_id'))
            ->filter()->unique()->values()->all();
        if ($ids === []) {
            return [];
        }

        $templates = PersonalizationTemplate::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->where('is_published', true)
            ->get(['id', 'fields'])
            ->keyBy('id');

        $counts = [];
        foreach ($products as $product) {
            $usable = collect($assigned->get($product->id, []))
                ->map(fn ($row) => $templates->get($row->personalization_template_id))
                ->filter(fn ($template): bool => $template !== null && self::enabledFields($template->fields) !== []);

            // ProductRoster uses the legacy FK only when no usable pivot
            // template exists; mirror that behavior to avoid false counts.
            if ($usable->isEmpty()) {
                $fallback = $templates->get($product->personalization_template_id);
                $usable = $fallback && self::enabledFields($fallback->fields) !== []
                    ? collect([$fallback]) : collect();
            }

            $seen = [];
            foreach ($usable as $template) {
                foreach (self::enabledFields($template->fields) as $field) {
                    $label = trim($field['label']);
                    $value = self::valueForLabel($label);
                    if (isset($seen[$value])) {
                        continue;
                    }
                    $seen[$value] = true;
                    $counts[$value] ??= ['value' => $value, 'label' => $label, 'count' => 0];
                    $counts[$value]['count']++;
                }
            }
        }

        return collect($counts)->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
    }

    /** @return array<int, int> */
    public function templateIdsForValue(string $value): array
    {
        if (! preg_match('/^field-[a-f0-9]{40}$/D', $value)) {
            return [];
        }

        if ($this->templateIdsByValue === null) {
            $mapping = [];
            PersonalizationTemplate::query()
                ->where('is_active', true)
                ->where('is_published', true)
                ->get(['id', 'fields'])
                ->each(function (PersonalizationTemplate $template) use (&$mapping): void {
                    foreach (self::enabledFields($template->fields) as $field) {
                        $mapping[self::valueForLabel($field['label'])][(int) $template->id] = (int) $template->id;
                    }
                });
            $this->templateIdsByValue = array_map('array_values', $mapping);
        }

        return $this->templateIdsByValue[$value] ?? [];
    }

    /** @return array<int, array{label:string}> */
    private static function enabledFields(?array $fields): array
    {
        return collect($fields ?? [])->filter(fn ($field): bool =>
            is_array($field)
            && ($field['enabled'] ?? true)
            && filled(trim((string) ($field['label'] ?? '')))
        )->values()->all();
    }

    private static function normalizeLabel(string $label): string
    {
        return Str::lower(preg_replace('/\s+/u', ' ', trim($label)) ?? trim($label));
    }
}
