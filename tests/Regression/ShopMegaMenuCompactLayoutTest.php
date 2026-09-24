<?php

$cssPath = dirname(__DIR__, 2).'/resources/css/storefront.css';
$css = file_get_contents($cssPath);

if ($css === false) {
    fwrite(STDERR, "Unable to read storefront.css\n");
    exit(1);
}

$checks = [
    'desktop Shop Products mega menu switches from row grid to column flow' => preg_match(
        '/@media\s*\(min-width:\s*1024px\)\s*\{(?:(?!@media).)*?\.np-shop-panel\s+\.np-mega-grid\s*\{[^}]*display:\s*block\s*;[^}]*column-count:\s*4\s*;/s',
        $css
    ) === 1,
    'desktop Shop Products cards keep their own height and do not split between columns' => preg_match(
        '/@media\s*\(min-width:\s*1024px\)\s*\{(?:(?!@media).)*?\.np-shop-panel\s+\.np-mega-card\s*\{[^}]*break-inside:\s*avoid\s*;[^}]*margin-bottom:\s*14px\s*;/s',
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
