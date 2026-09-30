<?php

$builderPath = __DIR__.'/../../resources/views/components/storefront/product/builder.blade.php';
$cssPath = __DIR__.'/../../resources/css/storefront.css';

$builder = file_get_contents($builderPath);
$css = file_get_contents($cssPath);

$errors = [];

if (! str_contains($builder, 'class="np-roster-remove-button"')) {
    $errors[] = 'Roster rows must use the dedicated remove button class.';
}

if (! str_contains($builder, '<span aria-hidden="true">×</span>')) {
    $errors[] = 'Roster remove action must show an x icon.';
}

if (! str_contains($builder, '<span>Remove</span>')) {
    $errors[] = 'Roster remove action must show the Remove label.';
}

if (str_contains($builder, 'aria-label="Clear player row"><svg')) {
    $errors[] = 'Legacy trash-only player-row action must be removed.';
}

if (! str_contains($css, '.storefront-clean-ui .np-roster-remove-button')) {
    $errors[] = 'Roster remove button styling is missing.';
}

if (! str_contains($css, 'color: var(--np-color-error);')) {
    $errors[] = 'Roster remove action must use the centralized error/red color.';
}

if ($errors) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo "Product detail roster remove button regression passed.\n";
