<?php

return [
    'max_category_depth' => (int) env('CATALOG_MAX_CATEGORY_DEPTH', 5),
    'category_page_size' => (int) env('CATALOG_CATEGORY_PAGE_SIZE', 24),
    'navigation_cache_seconds' => (int) env('CATALOG_NAVIGATION_CACHE_SECONDS', 3600),
    'category_cache_seconds' => (int) env('CATALOG_CATEGORY_CACHE_SECONDS', 1800),
    'facets_cache_seconds' => (int) env('CATALOG_FACETS_CACHE_SECONDS', 300),
];
