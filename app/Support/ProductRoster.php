<?php

namespace App\Support;

use App\Models\Product;

class ProductRoster
{
    public const DEFAULT_TITLE = 'Add item details';

    /**
     * Roster fields are intentionally product-agnostic.
     *
     * The capability method is retained as the single gate for older callers.
     * Every product profile can now opt into roster fields through its product
     * settings, so profile type no longer controls availability.
     */
    public static function supports(?string $productProfile = null): bool
    {
        return true;
    }

    /** @return array<int, array{key:string,label:string,type:string,max_length:int,required:bool,enabled:bool}> */
    public static function defaultFields(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'max_length' => 60, 'required' => false, 'enabled' => true],
            ['key' => 'number', 'label' => 'Number', 'type' => 'number', 'max_length' => 12, 'required' => false, 'enabled' => true],
        ];
    }

    /**
     * Build the generic storefront/cart roster contract from the persisted
     * product model. The database column names remain legacy-compatible so
     * existing installations do not need a destructive rename migration.
     *
     * @return array{enabled:bool,optional:bool,title:string,fields:array<int, array<string, mixed>>}
     */
    public static function forProduct(Product $product): array
    {
        // The catalog fetch eager-loads assignments. The single legacy FK is
        // retained for products saved before this migration.
        $templates = $product->relationLoaded('personalizationTemplates')
            ? $product->personalizationTemplates->filter(fn ($template) => $template->usable)->values()
            : collect();
        if ($templates->isEmpty() && $product->relationLoaded('personalizationTemplate')
            && $product->personalizationTemplate?->usable
) {
            $templates = collect([$product->personalizationTemplate]);
        }

        if ($templates->isNotEmpty()) {
            $multi = $templates->count() > 1;
            $sections = $templates->map(function ($template) use ($multi): array {
                $fields = collect($template->fields)
                    ->filter(fn ($field) => is_array($field) && ($field['enabled'] ?? true))
                    ->map(function (array $field) use ($template, $multi): array {
                        if ($multi) {
                            $field['key'] = 't'.$template->id.'__'.$field['key'];
                        }
                        return $field;
                    })->values()->all();

                return [
                    'id' => $template->id,
                    'title' => $template->section_title,
                    'description' => $template->description,
                    'shared_entry_enabled' => (bool) $template->shared_entry_enabled,
                    'shared_entry_label' => $template->shared_entry_label ?: 'Use the same details for all items',
                    'fields' => $fields,
                ];
            })->values()->all();
            $first = $sections[0];

            return [
                'enabled' => (bool) $product->jersey_roster_enabled,
                'optional' => false,
                'title' => $multi ? 'Item Personalization' : $first['title'],
                'description' => $multi ? '' : $first['description'],
                'template_id' => $first['id'],
                'multiple_templates' => $multi,
                'sections' => $sections,
                'shared_entry_enabled' => ! $multi && $first['shared_entry_enabled'],
                'shared_entry_label' => $first['shared_entry_label'],
                'fields' => collect($sections)->flatMap(fn ($section) => $section['fields'])->values()->all(),
            ];
        }

        // An assigned but unpublished/inactive template must not reveal legacy
        // product-specific fields as an unintended storefront fallback.
        if (($product->relationLoaded('personalizationTemplates') && $product->personalizationTemplates->isNotEmpty())
            || $product->personalization_template_id) {
            return ['enabled' => false, 'optional' => true, 'title' => self::DEFAULT_TITLE, 'fields' => []];
        }

        return [
            'enabled' => (bool) $product->jersey_roster_enabled,
            'optional' => (bool) $product->jersey_roster_optional,
            'title' => filled($product->jersey_roster_title)
                ? (string) $product->jersey_roster_title
                : self::DEFAULT_TITLE,
            'shared_entry_enabled' => true,
            'shared_entry_label' => 'Use the same name & number for all items',
            'fields' => collect($product->jersey_roster_fields ?? self::defaultFields())
                ->filter(fn ($field): bool => is_array($field) && (bool) ($field['enabled'] ?? true))
                ->values()->all(),
        ];
    }

    /**
     * Resolve a product-array roster configuration while supporting the legacy
     * `jersey_roster` payload during the transition to the generic `roster`
     * name. New code should publish/read `roster`; old fixtures and cached
     * payloads keep working through the fallback.
     *
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    public static function settings(array $product): array
    {
        $settings = $product['roster'] ?? $product['jersey_roster'] ?? [];

        return is_array($settings) ? $settings : [];
    }
}
