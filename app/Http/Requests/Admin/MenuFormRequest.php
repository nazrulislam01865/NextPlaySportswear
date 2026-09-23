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
        $items = collect($this->input('items', []))->map(function (array $item): array {
            $item['is_active'] = filter_var($item['is_active'] ?? false, FILTER_VALIDATE_BOOL);
            $item['parent_key'] = filled($item['parent_key'] ?? null) ? $item['parent_key'] : null;
            return $item;
        })->all();

        $this->merge([
            'slug' => Str::slug((string) $this->input('slug', $this->input('name'))),
            'location' => filled($this->input('location')) ? Str::slug((string) $this->input('location')) : null,
            'is_active' => $this->boolean('is_active'),
            'items' => $items,
        ]);
    }
}
