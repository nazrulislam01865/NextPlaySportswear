<?php

$css = file_get_contents(__DIR__ . '/../../resources/css/storefront.css');

$requiredSnippets = [
    '.np-about-container {',
    'var(--np-commerce-layout-max)',
    'var(--np-visible-page-inline-space)',
    '.np-about-gallery__grid {',
    'width: min(var(--np-commerce-layout-max), calc(100% - var(--np-visible-page-inline-space)))',
    '@media (min-width: 821px) and (max-width: 1023px)',
    'width: calc(100% - 48px);',
    'width: calc(100% - 32px);',
];

foreach ($requiredSnippets as $snippet) {
    if (! str_contains($css, $snippet)) {
        fwrite(STDERR, "Missing About layout alignment requirement: {$snippet}\n");
        exit(1);
    }
}

echo "About page layout alignment check passed.\n";
