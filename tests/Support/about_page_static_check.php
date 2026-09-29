<?php

$view = file_get_contents(__DIR__ . '/../../resources/views/storefront/content/about.blade.php');
$service = file_get_contents(__DIR__ . '/../../app/Services/Storefront/AboutPageService.php');

$viewRequirements = [
    ':seo="$seo"',
    "data_get(\$about, 'hero.title')",
    '<x-storefront.about.service-card',
    "data_get(\$about, 'what_we_do.cards', [])",
    '<x-storefront.about.process-step',
    "data_get(\$about, 'how_we_work.steps', [])",
    "data_get(\$about, 'gallery.items', [])",
    "data_get(\$about, 'cta.primary_label')",
    "data_get(\$about, 'help.button_label')",
];

$serviceRequirements = [
    'ABOUT NEXTPLAY',
    'Sportswear for teams, clubs and people who love to play.',
    'WHAT WE DO',
    'HOW WE WORK',
    'Explore custom options for your club, event or organisation.',
    'NEED HELP?',
    "'custom-teamwear'",
    "'sportswear-gear'",
    "'bulk-orders'",
    "'choose-product'",
    "'personalise'",
    "'review-details'",
    "'place-order'",
];

foreach ($viewRequirements as $snippet) {
    if (! str_contains($view, $snippet)) {
        fwrite(STDERR, "Missing About managed-view requirement: {$snippet}\n");
        exit(1);
    }
}

foreach ($serviceRequirements as $snippet) {
    if (! str_contains($service, $snippet)) {
        fwrite(STDERR, "Missing About default-service requirement: {$snippet}\n");
        exit(1);
    }
}

if (! str_contains($service, 'isSafeAboutPath') || ! str_contains($service, "\$segment === '..'")) {
    fwrite(STDERR, "About media resolution must reject malformed traversal paths before filesystem access.\n");
    exit(1);
}

if (! str_contains($service, 'mergeKnownShape')) {
    fwrite(STDERR, "About settings must normalize malformed scalar leaf types against defaults.\n");
    exit(1);
}

if (! str_contains($service, 'normalizeDestinations')) {
    fwrite(STDERR, "About storefront must re-normalize persisted button destinations before rendering.\n");
    exit(1);
}

if (! str_contains($service, "\$about['introduction']['fallback_asset'] = \$defaults['introduction']['fallback_asset'];")
    || ! str_contains($service, "\$about['help']['fallback_icon'] = \$defaults['help']['fallback_icon'];")) {
    fwrite(STDERR, "Introduction/help fallback metadata must remain code-owned.\n");
    exit(1);
}

echo "About page managed-content static structure check passed.\n";
