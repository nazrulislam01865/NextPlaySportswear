<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\CategoryOperationsController;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ReflectionClass;
use Tests\TestCase;

class CategorySpreadsheetReaderTest extends TestCase
{
    public function test_it_reads_the_first_worksheet_of_xlsx_and_xls_without_changing_the_csv_field_contract(): void
    {
        if (! class_exists(Spreadsheet::class)) {
            $this->markTestSkipped('Install phpoffice/phpspreadsheet to run the Excel import test.');
        }

        foreach (['xlsx', 'xls'] as $format) {
            $path = tempnam(sys_get_temp_dir(), 'category-import-');
            $book = new Spreadsheet();
            $sheet = $book->getActiveSheet();
            $sheet->fromArray([
                ['name', 'slug', 'parent_slug', 'status'],
                ['Basketball', 'basketball', '', 'active'],
                ['Jerseys', 'jerseys', 'basketball', 'active'],
            ]);
            $book->createSheet()->setCellValue('A1', 'Ignored');

            try {
                ($format === 'xlsx' ? new Xlsx($book) : new Xls($book))->save($path);
                $reader = (new ReflectionClass(CategoryOperationsController::class))->newInstanceWithoutConstructor();
                $records = (new ReflectionClass(CategoryOperationsController::class))
                    ->getMethod('readSpreadsheet')->invoke($reader, $path);

                $this->assertCount(2, $records);
                $this->assertSame('Basketball', $records[2]['name']);
                $this->assertSame('basketball', $records[3]['parent_slug']);
            } finally {
                $book->disconnectWorksheets();
                @unlink($path);
            }
        }
    }

    public function test_it_rejects_workbook_formulas_instead_of_evaluating_them(): void
    {
        if (! class_exists(Spreadsheet::class)) {
            $this->markTestSkipped('Install phpoffice/phpspreadsheet to run the Excel import test.');
        }

        $path = tempnam(sys_get_temp_dir(), 'category-import-');
        $book = new Spreadsheet();
        $book->getActiveSheet()->fromArray([['name', 'slug'], ['=1+1', 'jerseys']]);

        try {
            (new Xlsx($book))->save($path);
            $reader = (new ReflectionClass(CategoryOperationsController::class))->newInstanceWithoutConstructor();
            $this->expectException(ValidationException::class);
            (new ReflectionClass(CategoryOperationsController::class))->getMethod('readSpreadsheet')->invoke($reader, $path);
        } finally {
            $book->disconnectWorksheets();
            @unlink($path);
        }
    }
}
