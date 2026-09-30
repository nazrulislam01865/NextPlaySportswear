<?php

$root = dirname(__DIR__, 2);
$builderPath = $root.'/resources/views/components/storefront/product/builder.blade.php';
$builder = file_get_contents($builderPath);

if ($builder === false) {
    fwrite(STDERR, "Unable to read product builder Blade source.\n");
    exit(1);
}

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$expect(! str_contains($builder, '@endif@if'), 'Do not chain @endif and @if on one line; Blade compilation can misparse the compact sequence.');

$reviewStart = strpos($builder, '{{-- STEP 6: REVIEW & ADD TO CART --}}');
$reviewEnd = $reviewStart === false ? false : strpos($builder, '</form>', $reviewStart);
$review = ($reviewStart !== false && $reviewEnd !== false)
    ? substr($builder, $reviewStart, $reviewEnd - $reviewStart)
    : '';

$expect($review !== '', 'Review step source is present.');
foreach (preg_split('/\R/', $review) as $lineNumber => $line) {
    preg_match_all('/@(if|elseif|else|endif|foreach|endforeach|for|endfor|while|endwhile|forelse|endforelse)\b/', $line, $matches);
    $expect(count($matches[0]) <= 1, 'Review step keeps Blade structural directives on separate lines.');
}

if ($failures !== []) {
    fwrite(STDERR, "Product detail Blade directive safety regression failed:\n");
    foreach (array_unique($failures) as $failure) {
        fwrite(STDERR, " - {$failure}\n");
    }
    exit(1);
}

echo "Product detail Blade directive safety regression passed.\n";
