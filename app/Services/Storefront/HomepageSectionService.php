<?php

namespace App\Services\Storefront;

use App\Models\HomepageSection;
use App\Support\HomepageSectionRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class HomepageSectionService
{
    private const CACHE_KEY = 'storefront.homepage-sections.v5';

    /** @var array<int, array<string, mixed>>|null */
    private ?array $runtimeSections = null;

    /** @return array<int, array<string, mixed>> */
    public function sections(): array
    {
        if ($this->runtimeSections !== null) {
            return $this->runtimeSections;
        }

        $ttl = max(1, (int) config('storefront.homepage_sections_cache_seconds', 600));

        try {
            $sections = Cache::remember(self::CACHE_KEY, $ttl, fn (): array => $this->buildSections());
        } catch (QueryException $exception) {
            if (! str_contains(strtolower($exception->getMessage()), 'homepage_sections')) {
                throw $exception;
            }

            $sections = $this->definitionSections();
        }

        return $this->runtimeSections = array_values(array_filter($sections, 'is_array'));
    }

    public function flushCache(): void
    {
        $this->runtimeSections = null;
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<int, array<string, mixed>> */
    private function buildSections(): array
    {
        if (! Schema::hasTable('homepage_sections')) {
            return $this->definitionSections();
        }

        $rows = HomepageSection::query()
            ->active()
            ->whereNotIn('key', HomepageSectionRegistry::retiredKeys())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->keyBy('key');

        $sections = [];
        foreach (HomepageSectionRegistry::orderedDefinitions() as $definition) {
            $key = (string) $definition['key'];
            $row = $rows->get($key);

            if ($row && ! $row->is_active) {
                continue;
            }

            $sections[] = HomepageSectionRegistry::mergeForView($key, $row);
        }

        $customRows = $rows->reject(fn (HomepageSection $row, string $key): bool => HomepageSectionRegistry::definition($key) !== null || HomepageSectionRegistry::isRetired((string) $key));
        foreach ($customRows as $row) {
            if ($row->is_active) {
                $sections[] = HomepageSectionRegistry::mergeForView((string) $row->key, $row);
            }
        }

        return collect($sections)
            ->where('is_active', true)
            ->sortBy(fn (array $section): int => (int) ($section['sort_order'] ?? 0))
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function definitionSections(): array
    {
        return collect(HomepageSectionRegistry::orderedDefinitions())
            ->map(fn (array $definition): array => HomepageSectionRegistry::mergeForView((string) $definition['key']))
            ->where('is_active', true)
            ->values()
            ->all();
    }
}
