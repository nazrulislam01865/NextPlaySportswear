<?php

// Dependency-free checks for the Categories import panel redesign.
$root = dirname(__DIR__, 2);
$view = file_get_contents($root.'/resources/views/admin/categories/index.blade.php');
$css = file_get_contents($root.'/public/css/admin-category-import.css');

$checks = [
    'Dedicated page-scoped asset' => str_contains($view, "asset('css/admin-category-import.css')"),
    'Preserved import endpoint' => str_contains($view, "route('admin.categories.import')"),
    'Preserved CSV and Excel input' => str_contains($view, 'name="category_csv"') && str_contains($view, 'accept=".csv,.xlsx,.xls,'),
    'File chooser and selected name' => str_contains($view, 'data-category-file-input') && str_contains($view, 'data-category-file-name'),
    'Error is displayed inline' => str_contains($view, "@error('category_csv')"),
    'Guide and export action' => str_contains($view, 'category-import-guide') && str_contains($view, "route('admin.categories.export')"),
    'No premature form submit' => str_contains($view, 'data-category-import-submit disabled') && str_contains($view, 'submit.disabled = !validFile'),
    'Client file limit matches help' => str_contains($view, '2 * 1024 * 1024') && str_contains($view, 'Max 2 MB'),
    'CSS hides native input' => str_contains($css, '#category-csv-import') && str_contains($css, 'opacity: 0 !important'),
    'CSS stays scoped to admin category page' => substr_count($css, '#admin-main-content') > 15,
    'Small screen layouts' => str_contains($css, '@media (max-width: 900px)') && str_contains($css, '@media (max-width: 560px)'),
];
$failed = array_keys(array_filter($checks, static fn (bool $ok) => ! $ok));
if ($failed) {
    fwrite(STDERR, implode("\n", $failed)."\n");
    exit(1);
}
echo 'Category import visual layout checks passed ('.count($checks)." checks).\n";
