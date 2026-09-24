<?php

$cssPath = dirname(__DIR__, 2).'/resources/css/storefront.css';
$css = file_get_contents($cssPath);

if ($css === false) {
    fwrite(STDERR, "Unable to read storefront.css\n");
    exit(1);
}

$failures = [];

$checks = [
    'center-aligned shared section titles own their horizontal centering' => preg_match(
        '/\.storefront-clean-ui\s+\.home-page\s+\.np-home-section-heading\s+\.np-home-section-title\s*\{[^}]*margin:\s*0\s+auto\s*!important\s*;/s',
        $css
    ) === 1,
    'left-aligned shared section titles explicitly opt out of centering' => preg_match(
        '/\.storefront-clean-ui\s+\.home-page\s+\.np-home-section-heading--left\s+\.np-home-section-title\s*\{[^}]*margin-left:\s*0\s*!important\s*;[^}]*margin-right:\s*0\s*!important\s*;/s',
        $css
    ) === 1,
    'How It Works no longer owns heading alignment outside the shared heading contract' => preg_match_all(
        '/\.storefront-clean-ui\s+\.home-page\s+\.process-intro\s*\{([^}]*)\}/s',
        $css,
        $processHeadingMatches
    ) >= 1
        && count(array_filter($processHeadingMatches[1], static fn (string $body): bool => preg_match('/\btext-align\s*:/', $body) === 1)) === 0,
    'How It Works no longer owns title positioning outside the shared heading contract' => preg_match_all(
        '/\.storefront-clean-ui\s+\.home-page\s+\.process-intro\s+h2\s*\{([^}]*)\}/s',
        $css,
        $processTitleMatches
    ) >= 1
        && count(array_filter($processTitleMatches[1], static fn (string $body): bool => preg_match('/\b(?:margin|max-width)\s*:/', $body) === 1)) === 0,
];

foreach ($checks as $label => $passed) {
    if (!$passed) {
        $failures[] = $label;
    }
}

if ($failures !== []) {
    fwrite(STDERR, "Home section heading alignment regression failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Home section heading alignment regression passed.\n";
