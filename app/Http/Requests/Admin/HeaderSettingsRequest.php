<?php

namespace App\Http\Requests\Admin;

use App\Rules\SafePublicUrl;
use Illuminate\Foundation\Http\FormRequest;

class HeaderSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'branding' => ['nullable', 'array'],
            'branding.logo' => ['nullable', 'string', 'max:2048'],
            'branding.logo_alt' => ['nullable', 'string', 'max:120'],
            'branding.logo_file' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:4096',
                'dimensions:max_width=4000,max_height=2000',
            ],
            'branding.remove_logo' => ['nullable', 'boolean'],

            'announcements' => ['nullable', 'array', 'max:12'],
            'announcements.*.enabled' => ['nullable', 'boolean'],
            'announcements.*.text' => ['nullable', 'string', 'max:255'],
            'announcements.*.url' => $this->urlRules(),
            'announcements.*.dismissible' => ['nullable', 'boolean'],

            'utility_links' => ['nullable', 'array', 'max:12'],
            'utility_links.*.enabled' => ['nullable', 'boolean'],
            'utility_links.*.label' => ['nullable', 'string', 'max:120'],
            'utility_links.*.url' => $this->urlRules(),
            'utility_links.*.icon' => ['nullable', 'string', 'max:2048'],
            'utility_links.*.icon_file' => $this->iconRules(),
            'utility_links.*.remove_icon' => ['nullable', 'boolean'],

            'search_enabled' => ['nullable', 'boolean'],
            'search_label' => ['nullable', 'string', 'max:80'],
            'search_url' => $this->urlRules(),
            'account_enabled' => ['nullable', 'boolean'],
            'wishlist_enabled' => ['nullable', 'boolean'],
            'cart_enabled' => ['nullable', 'boolean'],
            'quote_enabled' => ['nullable', 'boolean'],
            'quote_label' => ['nullable', 'string', 'max:120'],
            'quote_url' => $this->urlRules(),
        ];
    }

    /** @return array<string, mixed> */
    public function settings(): array
    {
        $settings = [
            'branding' => [
                'logo' => $this->cleanValue(data_get($this->input('branding', []), 'logo')),
                'logo_alt' => $this->cleanValue(data_get($this->input('branding', []), 'logo_alt')),
            ],
            'announcements' => $this->cleanAnnouncements((array) $this->input('announcements', [])),
            'utility_links' => $this->cleanUtilityLinks((array) $this->input('utility_links', [])),
            'actions' => [
                'search' => [
                    'enabled' => $this->boolean('search_enabled'),
                    'label' => $this->cleanScalar('search_label'),
                    'url' => $this->cleanScalar('search_url'),
                ],
                'account_enabled' => $this->boolean('account_enabled'),
                'wishlist_enabled' => $this->boolean('wishlist_enabled'),
                'cart_enabled' => $this->boolean('cart_enabled'),
                'quote' => [
                    'enabled' => $this->boolean('quote_enabled'),
                    'label' => $this->cleanScalar('quote_label'),
                    'url' => $this->cleanScalar('quote_url'),
                ],
            ],
        ];

        return $settings;
    }

    /** @return array<int, mixed> */
    private function urlRules(): array
    {
        return ['nullable', 'string', 'max:2048', new SafePublicUrl()];
    }

    /** @return array<int, mixed> */
    private function iconRules(): array
    {
        return [
            'nullable',
            'image',
            'mimes:png,jpg,jpeg,webp',
            'max:2048',
            'dimensions:max_width=1024,max_height=1024',
        ];
    }

    /** @param array<string|int, mixed> $rows @return array<int, array<string, mixed>> */
    private function cleanAnnouncements(array $rows): array
    {
        $clean = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $text = $this->cleanValue($row['text'] ?? null);
            if ($text === null) {
                continue;
            }

            $clean[] = [
                'enabled' => filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'text' => $text,
                'url' => $this->cleanValue($row['url'] ?? null),
                'dismissible' => filter_var($row['dismissible'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        return $clean;
    }

    /** @param array<string|int, mixed> $rows @return array<int, array<string, mixed>> */
    private function cleanUtilityLinks(array $rows): array
    {
        $clean = [];

        foreach ($rows as $rowKey => $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = $this->cleanValue($row['label'] ?? null);
            $url = $this->cleanValue($row['url'] ?? null);
            if ($label === null || $url === null) {
                continue;
            }

            $clean[] = [
                '_row_key' => (string) $rowKey,
                'enabled' => filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'label' => $label,
                'url' => $url,
                'icon' => $this->cleanValue($row['icon'] ?? null),
            ];
        }

        return $clean;
    }

    private function cleanScalar(string $field): ?string
    {
        return $this->cleanValue($this->input($field));
    }

    private function cleanValue(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
