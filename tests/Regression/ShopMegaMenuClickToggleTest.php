<?php

$root = dirname(__DIR__, 2);
$blade = file_get_contents($root.'/resources/views/components/storefront/menu/desktop-item.blade.php');
$js = file_get_contents($root.'/resources/js/storefront.js');
$css = file_get_contents($root.'/resources/css/storefront.css');

if ($blade === false || $js === false || $css === false) {
    fwrite(STDERR, "Unable to read Shop by Category menu sources.\n");
    exit(1);
}

$checks = [
    'Shop mega menu root has a dedicated click-only class' => str_contains($blade, "'np-shop-menu-item' => \$isShopMega"),
    'Shop mega menu trigger is explicitly identifiable' => str_contains($blade, 'data-shop-mega-trigger'),
    'Shop mega menu uses a right-pointing chevron' => str_contains($blade, '<path d="m9 18 6-6-6-6"'),
    'Open Shop mega menu rotates the chevron downward' => preg_match(
        '/\.np-shop-menu-item\.is-open\s*>\s*\.np-menu-link\s+\.np-category-caret\s*\{[^}]*transform:\s*rotate\(90deg\)/s',
        $css
    ) === 1,
    'Shop mega menu trigger click toggles the open state' => preg_match(
        '/if\s*\(isClickOnlyShopMega\)\s*\{(?:(?!\n\s*\}).)*?trigger\.addEventListener\(\'click\'/s',
        $js
    ) === 1,
    'Shop mega menu does not close merely because the pointer leaves it' => str_contains($js, "if (!isClickOnlyShopMega) {\n            item.addEventListener('pointerleave', () => setOpen(false), { passive: true });\n        }"),
    'Shop mega menu hover and focus cannot display the panel unless it is open' => preg_match(
        '/\.np-shop-menu-item:not\(\.is-open\):hover\s*>\s*\.np-shop-panel,\s*\.np-shop-menu-item:not\(\.is-open\):focus-within\s*>\s*\.np-shop-panel\s*\{\s*display:\s*none\s*;/s',
        $css
    ) === 1,
    'Shop mega menu displays from explicit open state' => preg_match(
        '/\.np-shop-menu-item\.is-open\s*>\s*\.np-shop-panel\s*\{\s*display:\s*block\s*;/s',
        $css
    ) === 1,
];

$failed = false;
foreach ($checks as $description => $passed) {
    if (! $passed) {
        $failed = true;
        fwrite(STDERR, "FAIL: {$description}\n");
    } else {
        fwrite(STDOUT, "PASS: {$description}\n");
    }
}

exit($failed ? 1 : 0);
