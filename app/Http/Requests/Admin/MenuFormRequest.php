<?php

namespace App\Http\Requests\Admin;

use App\Rules\SafePublicUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MenuFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $menuId = $this->route('menu')?->getKey();

        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('menus', 'slug')->ignore($menuId)],
            'location' => ['nullable', 'string', 'max:80', Rule::unique('menus', 'location')->ignore($menuId)],
            'is_active' => ['required', 'boolean'],
            'items' => ['nullable', 'array', 'max:300'],
            'items.*.key' => ['required', 'string', 'max:100', 'distinct'],
            'items.*.parent_key' => ['nullable', 'string', 'max:100'],
            'items.*.label' => ['nullable', 'string', 'max:180'],
            'items.*.link_type' => ['required', Rule::in(['category', 'route', 'custom'])],
            'items.*.category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'items.*.route_name' => ['nullable', 'string', 'max:180'],
            'items.*.url' => ['nullable', 'string', 'max:2048', new SafePublicUrl()],
            'items.*.target' => ['required', Rule::in(['_self', '_blank'])],
            'items.*.css_class' => ['nullable', 'string', 'max:255'],
            'items.*.is_active' => ['required', 'boolean'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = collect($this->input('items', []))->filter(fn ($item) => filled($item['label'] ?? null));
            $keys = $items->pluck('key')->all();

            foreach ($items as $index => $item) {
                $type = $item['link_type'] ?? 'category';
                if ($type === 'category' && empty($item['category_id'])) {
                    $validator->errors()->add("items.{$index}.category_id", 'Choose a category for this menu item.');
                }
                if ($type === 'route' && empty($item['route_name'])) {
                    $validator->errors()->add("items.{$index}.route_name", 'Enter a named route for this menu item.');
                } elseif ($type === 'route' && ! app('router')->has((string) $item['route_name'])) {
                    $validator->errors()->add("items.{$index}.route_name", 'The selected named route does not exist.');
                }
                if ($type === 'custom' && empty($item['url'])) {
                    $validator->errors()->add("items.{$index}.url", 'Enter a URL for this menu item.');
                }

                $parentKey = $item['parent_key'] ?? null;
                if ($parentKey && (! in_array($parentKey, $keys, true) || $parentKey === ($item['key'] ?? null))) {
                    $validator->errors()->add("items.{$index}.parent_key", 'Choose a valid different parent item.');
                }
            }

            foreach ($items as $index => $item) {
                $seen = [$item['key'] ?? ''];
                $parentKey = $item['parent_key'] ?? null;
                while ($parentKey) {
                    if (in_array($parentKey, $seen, true)) {
                        $validator->errors()->add("items.{$index}.parent_key", 'Circular menu hierarchy is not allowed.');
                        break;
                    }
                    $seen[] = $parentKey;
                    $parentKey = $items->firstWhere('key', $parentKey)['parent_key'] ?? null;
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $location = filled($this->input('location'))
            ? Str::slug((string) $this->input('location'))
            : null;

        $items = collect($this->input('items', []))->map(function (array $item): array {
            $item['is_active'] = filter_var($item['is_active'] ?? false, FILTER_VALIDATE_BOOL);
            $item['parent_key'] = filled($item['parent_key'] ?? null) ? (string) $item['parent_key'] : null;
            return $item;
        })->values()->all();

        if ($location === 'header-primary') {
            $items = $this->normalizeHeaderItems($items);
        }

        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
            'location' => $location,
            'is_active' => $this->boolean('is_active'),
            'items' => $items,
        ]);
    }

    /**
     * The primary header owns only the configured top-level links. Shop Products
     * descendants are generated from the live category tree, so stale legacy rows
     * must never participate in validation or be written back. Exact duplicate
     * top-level rows are also collapsed so a repeated submit cannot preserve
     * duplicate header links.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeHeaderItems(array $items): array
    {
        $rows = collect($items)->values();
        $byKey = $rows
            ->filter(fn (array $item): bool => filled($item['key'] ?? null))
            ->keyBy(fn (array $item): string => (string) $item['key']);

        $shopKeys = $rows
            ->filter(fn (array $item): bool => $this->isShopNavigationRow($item))
            ->pluck('key')
            ->filter(fn (mixed $key): bool => filled($key))
            ->map(fn (mixed $key): string => (string) $key)
            ->values()
            ->all();

        if ($shopKeys !== []) {
            $rows = $rows->reject(function (array $item) use ($byKey, $shopKeys): bool {
                $parentKey = filled($item['parent_key'] ?? null) ? (string) $item['parent_key'] : null;
                $seen = [];

                while ($parentKey) {
                    if (in_array($parentKey, $shopKeys, true)) {
                        return true;
                    }

                    if (isset($seen[$parentKey])) {
                        break;
                    }

                    $seen[$parentKey] = true;
                    $parent = $byKey->get($parentKey);
                    $parentKey = is_array($parent) && filled($parent['parent_key'] ?? null)
                        ? (string) $parent['parent_key']
                        : null;
                }

                return false;
            })->values();
        }

        $seenRootSignatures = [];
        $duplicateKeys = [];
        $normalized = [];

        foreach ($rows as $item) {
            if (filled($item['parent_key'] ?? null)) {
                $normalized[] = $item;
                continue;
            }

            $signature = $this->headerRootSignature($item);
            if (isset($seenRootSignatures[$signature])) {
                if (filled($item['key'] ?? null)) {
                    $duplicateKeys[(string) $item['key']] = $seenRootSignatures[$signature];
                }
                continue;
            }

            $key = filled($item['key'] ?? null) ? (string) $item['key'] : '';
            $seenRootSignatures[$signature] = $key;
            $normalized[] = $item;
        }

        if ($duplicateKeys !== []) {
            foreach ($normalized as &$item) {
                $parentKey = filled($item['parent_key'] ?? null) ? (string) $item['parent_key'] : null;
                $seen = [];

                while ($parentKey && isset($duplicateKeys[$parentKey]) && ! isset($seen[$parentKey])) {
                    $seen[$parentKey] = true;
                    $parentKey = $duplicateKeys[$parentKey] ?: null;
                }

                $item['parent_key'] = $parentKey;
            }
            unset($item);
        }

        return array_values($normalized);
    }

    /** @param array<string, mixed> $item */
    private function isShopNavigationRow(array $item): bool
    {
        $label = Str::of((string) ($item['label'] ?? ''))
            ->lower()
            ->replace(['-', '_'], ' ')
            ->squish()
            ->toString();

        return (($item['link_type'] ?? null) === 'route' && ($item['route_name'] ?? null) === 'categories.index')
            || in_array($label, ['shop products', 'shop categories', 'categories'], true);
    }

    /** @param array<string, mixed> $item */
    private function headerRootSignature(array $item): string
    {
        $type = (string) ($item['link_type'] ?? 'category');
        $destination = match ($type) {
            'category' => (string) ($item['category_id'] ?? ''),
            'route' => trim((string) ($item['route_name'] ?? '')),
            'custom' => trim((string) ($item['url'] ?? '')),
            default => '',
        };

        return implode('|', [
            Str::of((string) ($item['label'] ?? ''))->lower()->squish()->toString(),
            $type,
            $destination,
            (string) ($item['target'] ?? '_self'),
            trim((string) ($item['css_class'] ?? '')),
            filter_var($item['is_active'] ?? false, FILTER_VALIDATE_BOOL) ? '1' : '0',
        ]);
    }
}
