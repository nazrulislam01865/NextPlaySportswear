<?php

$root = dirname(__DIR__, 2);
$homePath = $root.'/resources/views/storefront/home.blade.php';
$registryPath = $root.'/app/Support/HomepageSectionRegistry.php';
$buyerPathsComponentPath = $root.'/resources/views/components/storefront/home/buyer-paths.blade.php';
$whyChooseComponentPath = $root.'/resources/views/components/storefront/home/why-choose.blade.php';

$home = file_get_contents($homePath);
$registry = file_get_contents($registryPath);

if ($home === false || $registry === false) {
    fwrite(STDERR, "Unable to read homepage section sources.\n");
    exit(1);
}

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$homeWithoutBladeComments = preg_replace('/\{\{--.*?--\}\}/s', '', $home) ?? $home;

$expect(
    str_contains($home, "@case('buyer_paths')")
        && preg_match('/\{\{--\s*<x-storefront\.home\.buyer-paths\b.*?\/?>\s*--\}\}/s', $home) === 1
        && ! str_contains($homeWithoutBladeComments, '<x-storefront.home.buyer-paths'),
    'Buyer paths remains in the homepage switch but its render call is Blade-commented out.'
);

$expect(
    str_contains($home, "@case('why_choose')")
        && preg_match('/\{\{--\s*<x-storefront\.home\.why-choose\b.*?\/?>\s*--\}\}/s', $home) === 1
        && ! str_contains($homeWithoutBladeComments, '<x-storefront.home.why-choose'),
    'Why choose remains in the homepage switch but its render call is Blade-commented out.'
);

$expect(
    file_exists($buyerPathsComponentPath)
        && file_exists($whyChooseComponentPath)
        && str_contains($registry, "'buyer_paths'")
        && str_contains($registry, "'why_choose'"),
    'Both hidden homepage sections keep their component files and registry definitions intact.'
);

if ($failures !== []) {
    fwrite(STDERR, "Homepage temporarily hidden sections regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Homepage temporarily hidden sections regression passed.\n";
