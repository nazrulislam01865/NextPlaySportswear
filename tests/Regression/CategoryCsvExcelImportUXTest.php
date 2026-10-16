<?php

// Dependency-free checks for category import format handling and scoped UX.
$root = dirname(__DIR__, 2);
$controller = file_get_contents($root.'/app/Http/Controllers/Admin/CategoryOperationsController.php');
$request = file_get_contents($root.'/app/Http/Requests/Admin/CategoryImportRequest.php');
$view = file_get_contents($root.'/resources/views/admin/categories/index.blade.php');
$styles = file_get_contents($root.'/resources/css/admin.css');

$checks = [
    'CSV, XLSX and XLS validation' => str_contains($request, "extensions:csv,xlsx,xls") && str_contains($request, 'mimes:csv,txt,xlsx,xls'),
    'Import uses existing route and field' => str_contains($view, "route('admin.categories.import')") && str_contains($view, 'name="category_csv"'),
    'Both CSV and spreadsheet readers' => str_contains($controller, '$this->readCsv(') && str_contains($controller, '$this->readSpreadsheet('),
    'Bounded spreadsheet upload' => str_contains($controller, "['totalRows'] > 1001") && str_contains($controller, "['totalColumns'] > 40"),
    'Spreadsheet formula rejection' => str_contains($controller, 'DataType::TYPE_FORMULA'),
    'Preserve category transactions' => str_contains($controller, 'DB::transaction(function () use ($records)'),
    'Preserve parent relationships' => str_contains($controller, '$this->treeService->assertValidParent('),
    'Import file formats shown' => str_contains($view, 'CSV · XLSX · XLS') && str_contains($view, 'accept=".csv,.xlsx,.xls,'),
    'Clearly named input and action' => str_contains($view, 'Choose a CSV or Excel file') && str_contains($view, 'Import categories'),
    'Import helper text' => str_contains($view, 'Uses the first Excel worksheet') && str_contains($view, 'Up to 1,000 categories'),
    'Selected filename feedback' => str_contains($view, 'data-category-file-name') && str_contains($view, 'data-category-import-submit'),
    'Inline import validation' => str_contains($view, "@error('category_csv')"),
    'Scoped styles' => str_contains($styles, '#admin-main-content .category-upload-picker'),
];

$failures = array_keys(array_filter($checks, fn (bool $passed) => ! $passed));
if ($failures) {
    fwrite(STDERR, implode("\n", $failures)."\n");
    exit(1);
}

echo 'Category CSV / Excel import UI checks passed ('.count($checks)." checks).\n";
