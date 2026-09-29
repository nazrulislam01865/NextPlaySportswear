<?php

if (! function_exists('filled')) {
    function filled(mixed $value): bool
    {
        return ! ($value === null || $value === '' || $value === []);
    }
}

require __DIR__ . '/../../app/Support/PublicUrl.php';
require __DIR__ . '/../../app/Services/Storefront/AboutPageService.php';

$service = new App\Services\Storefront\AboutPageService();
$method = new ReflectionMethod($service, 'isAllowedDestination');
$method->setAccessible(true);

$cases = [
    '/products' => true,
    '/products?sort=newest' => true,
    'https://example.com/about' => true,
    '//evil.example/path' => false,
    'javascript:alert(1)' => false,
    '/bad path' => false,
    '/bad\\path' => false,
];

foreach ($cases as $value => $expected) {
    $actual = $method->invoke($service, $value);
    if ($actual !== $expected) {
        fwrite(STDERR, sprintf("FAIL %s expected %s got %s\n", $value, $expected ? 'true' : 'false', $actual ? 'true' : 'false'));
        exit(1);
    }
}

echo "about destination runtime check passed\n";
