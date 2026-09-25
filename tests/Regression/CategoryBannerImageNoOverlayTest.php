<?php

$root = dirname(__DIR__, 2);
$viewPath = $root.'/resources/views/storefront/categories/show.blade.php';
$view = file_get_contents($viewPath);

if ($view === false) {
    fwrite(STDERR, "Unable to read category detail view.\n");
    exit(1);
}

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$expect(
    ! preg_match('/class="[^"]*opacity-(?:[0-9]+|\[[^\]]+\])[^"]*"/', $view),
    'Category banner image is rendered at full opacity.'
);
$expect(
    ! str_contains($view, 'bg-gradient-to-r from-brand-dark/95 via-brand-navy/80 to-brand-navy/35'),
    'Category banner does not render the dark gradient overlay.'
);
$expect(
    str_contains($view, '@if(blank($categoryBannerImage) && filled($categoryBannerColor))'),
    'Configured banner color is applied only when there is no banner image.'
);
$expect(
    str_contains($view, "filled(\$categoryBannerImage) ? '' : 'bg-brand-dark'"),
    'Default dark banner background is disabled whenever an image is present.'
);
$expect(
    str_contains($view, 'h-full w-full object-cover'),
    'Category banner keeps the responsive object-cover image behavior.'
);

if ($failures !== []) {
    fwrite(STDERR, "Category banner no-overlay regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Category banner no-overlay regression passed.\n";
