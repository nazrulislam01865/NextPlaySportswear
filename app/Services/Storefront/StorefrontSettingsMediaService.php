<?php

namespace App\Services\Storefront;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StorefrontSettingsMediaService
{
    private const MANAGED_ROOT = 'storefront/settings/';
    private const ICON_PREFIX = self::MANAGED_ROOT.'icons/';
    private const BRANDING_PREFIX = self::MANAGED_ROOT.'branding/';

    /** @param array<string, mixed> $settings */
    public function prepareHeader(Request $request, array $settings): array
    {
        $settings['branding']['logo'] = $this->resolveMedia(
            $request,
            'branding',
            data_get($settings, 'branding.logo'),
            self::BRANDING_PREFIX,
            'logo_file',
            'remove_logo'
        );


        foreach ((array) ($settings['utility_links'] ?? []) as $index => $link) {
            if (! is_array($link)) {
                continue;
            }

            $rowKey = (string) ($link['_row_key'] ?? $index);
            $settings['utility_links'][$index]['icon'] = $this->resolveIcon(
                $request,
                "utility_links.$rowKey",
                $link['icon'] ?? null,
                self::ICON_PREFIX.'header/utility'
            );
            unset($settings['utility_links'][$index]['_row_key']);
        }

        return $settings;
    }


    /** @param array<string, mixed> $settings */
    public function prepareNavigation(Request $request, array $settings): array
    {
        foreach ((array) ($settings['items'] ?? []) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $rowKey = (string) ($item['_row_key'] ?? $index);
            $promo = data_get($item, 'mega_menu.promo');
            if (is_array($promo)) {
                $settings['items'][$index]['mega_menu']['promo']['image'] = $this->resolveMedia(
                    $request,
                    "navigation.items.$rowKey.mega_menu.promo",
                    $promo['image'] ?? null,
                    self::MANAGED_ROOT.'navigation/mega-menu',
                    'image_file',
                    'remove_image'
                );
            }

            unset($settings['items'][$index]['_row_key']);
        }

        return $settings;
    }

    /** @param array<string, mixed> $settings */
    public function prepareFooter(Request $request, array $settings): array
    {
        foreach ((array) ($settings['columns'] ?? []) as $columnIndex => $column) {
            if (! is_array($column)) {
                continue;
            }

            $columnKey = (string) ($column['_row_key'] ?? $columnIndex);
            foreach ((array) ($column['items'] ?? []) as $itemIndex => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $itemKey = (string) ($item['_row_key'] ?? $itemIndex);
                $settings['columns'][$columnIndex]['items'][$itemIndex]['icon'] = $this->resolveIcon(
                    $request,
                    "columns.$columnKey.items.$itemKey",
                    $item['icon'] ?? null,
                    self::ICON_PREFIX.'footer/menu'
                );
                unset($settings['columns'][$columnIndex]['items'][$itemIndex]['_row_key']);
            }

            unset($settings['columns'][$columnIndex]['_row_key']);
        }

        foreach ((array) data_get($settings, 'social.links', []) as $index => $link) {
            if (! is_array($link)) {
                continue;
            }

            $rowKey = (string) ($link['_row_key'] ?? $index);
            $settings['social']['links'][$index]['icon'] = $this->resolveIcon(
                $request,
                "social_links.$rowKey",
                $link['icon'] ?? null,
                self::ICON_PREFIX.'footer/social'
            );
            unset($settings['social']['links'][$index]['_row_key']);
        }

        foreach ((array) data_get($settings, 'legal.links', []) as $index => $link) {
            if (! is_array($link)) {
                continue;
            }

            $rowKey = (string) ($link['_row_key'] ?? $index);
            $settings['legal']['links'][$index]['icon'] = $this->resolveIcon(
                $request,
                "legal_links.$rowKey",
                $link['icon'] ?? null,
                self::ICON_PREFIX.'footer/legal'
            );
            unset($settings['legal']['links'][$index]['_row_key']);
        }

        return $settings;
    }

    /**
     * Remove managed storefront media files that existed before the update but are no
     * longer referenced after the update.
     *
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    public function cleanupRemovedMedia(array $before, array $after): void
    {
        $removed = array_diff($this->managedMediaPaths($before), $this->managedMediaPaths($after));

        if ($removed !== []) {
            Storage::disk('public')->delete(array_values($removed));
        }
    }

    /**
     * If persistence fails after new files were stored, remove only files that
     * were introduced by the failed request.
     *
     * @param array<string, mixed> $before
     * @param array<string, mixed> $prepared
     */
    public function cleanupNewUploads(array $before, array $prepared): void
    {
        $new = array_diff($this->managedMediaPaths($prepared), $this->managedMediaPaths($before));

        if ($new !== []) {
            Storage::disk('public')->delete(array_values($new));
        }
    }

    private function resolveIcon(Request $request, string $fieldPrefix, mixed $existingIcon, string $directory): ?string
    {
        return $this->resolveMedia($request, $fieldPrefix, $existingIcon, $directory, 'icon_file', 'remove_icon');
    }

    private function resolveMedia(
        Request $request,
        string $fieldPrefix,
        mixed $existingValue,
        string $directory,
        string $fileField,
        string $removeField,
    ): ?string {
        $existing = $this->cleanValue($existingValue);

        if ($request->boolean($fieldPrefix.'.'.$removeField)) {
            $existing = null;
        }

        $uploaded = $request->file($fieldPrefix.'.'.$fileField);
        if ($uploaded instanceof UploadedFile) {
            $path = $uploaded->store($directory, 'public');

            return '/storage/'.$path;
        }

        return $existing;
    }

    /** @param array<string, mixed> $settings @return array<int, string> */
    private function managedMediaPaths(array $settings): array
    {
        $paths = [];
        $this->collectManagedMediaPaths($settings, $paths);

        return array_values(array_unique($paths));
    }

    /** @param array<string|int, mixed> $value @param array<int, string> $paths */
    private function collectManagedMediaPaths(array $value, array &$paths): void
    {
        foreach ($value as $key => $item) {
            if (in_array($key, ['icon', 'logo', 'image'], true) && is_string($item)) {
                $path = $this->managedPathFromUrl($item);
                if ($path !== null) {
                    $paths[] = $path;
                }
                continue;
            }

            if (is_array($item)) {
                $this->collectManagedMediaPaths($item, $paths);
            }
        }
    }

    private function managedPathFromUrl(string $url): ?string
    {
        $url = trim($url);
        $prefix = '/storage/'.self::MANAGED_ROOT;

        if (! str_starts_with($url, $prefix)) {
            return null;
        }

        return ltrim(substr($url, strlen('/storage/')), '/');
    }

    private function cleanValue(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }
}
