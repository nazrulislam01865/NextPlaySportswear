<?php
/**
 * Homepage FAQ/Quick Help alignment contract. Runs without Composer or a DB.
 * A single parent grid row owns BOTH headings, so optional subtitles cannot
 * independently move the first accordion or the Quick Help card grid.
 */
$root = dirname(__DIR__, 2);
$faq = file_get_contents($root.'/resources/views/components/storefront/home/faq.blade.php');
$quick = file_get_contents($root.'/resources/views/components/storefront/home/quick-help.blade.php');
$css = file_get_contents($root.'/resources/css/storefront-typography.css');
$published = file_get_contents($root.'/public/css/storefront-typography.css');
$layout = file_get_contents($root.'/resources/views/components/layouts/storefront.blade.php');

$checks = [
    'Main heading belongs to parent grid' => str_contains($faq, 'class="section-head np-home-faq-main-heading"'),
    'Quick Help heading belongs to same parent grid' => str_contains($faq, 'class="section-head np-home-quick-help-heading"'),
    'The two headings are direct siblings of the card containers' => strpos($faq, 'np-home-faq-main-heading') < strpos($faq, 'np-home-quick-help-heading') && strpos($faq, 'np-home-quick-help-heading') < strpos($faq, 'np-home-faq-column'),
    'Optional Quick Help description remains admin-configurable' => str_contains($faq, "\$settings['quick_help_description'] ?? 'Find what you need.'"),
    'Quick Help card markup stays in its reusable component' => str_contains($quick, 'class="np-home-quick-help-card"'),
    'Heading is not duplicated inside card aside on the homepage' => str_contains($faq, ':show-heading="false"') && str_contains($quick, '@if($showHeading)'),
    'Quick Help aside retains its accessible heading reference' => str_contains($quick, 'aria-labelledby="np-home-quick-help-title"'),
    'Parent grid owns one shared heading track' => str_contains($css, 'grid-template-rows: max-content auto'),
    'FAQ content is explicitly placed in second row' => str_contains($css, '> .np-home-faq-column {'."\n".'        grid-column: 1;'."\n".'        grid-row: 2;'),
    'Help card grid is explicitly placed in second row' => str_contains($css, '> .np-home-faq-aside {'."\n".'        grid-column: 2;'."\n".'        grid-row: 2;'),
    'Heading-specific margins cannot cause the earlier offset' => str_contains($css, 'margin: 0 !important;'),
    'Mobile layout preserves FAQ before Quick Help' => str_contains($css, '> .np-home-quick-help-heading {grid-row: 3;') && str_contains($css, '> .np-home-faq-aside {grid-row: 4;'),
    'Single-column FAQ mode is retained' => str_contains($faq, 'np-home-faq-layout--single'),
    'No fragile subgrid dependency remains' => !str_contains($css, 'grid-template-rows: subgrid'),
    'Public CSS generated from the same source' => str_contains($published, 'grid-template-rows: max-content auto'),
    'Stylesheet URL is cache-busted' => str_contains($layout, '?v=20261031faqsharedrow2'),
];
$fails = array_keys(array_filter($checks, fn ($ok) => ! $ok));
if ($fails) {
    fwrite(STDERR, "FAQ alignment checks failed:\n - ".implode("\n - ", $fails)."\n");
    exit(1);
}
echo count($checks)." homepage FAQ structural alignment checks passed.\n";
