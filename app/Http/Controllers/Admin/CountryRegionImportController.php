<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CountryCallingCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Services\Geography\ImportMissingGeography;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CountryRegionImportController extends Controller
{
    /** Download a small, country-specific template; country information is not repeated per row. */
    public function template(CountryCallingCode $countryCallingCode): StreamedResponse
    {
        abort_unless(auth('admin')->user()?->canAdmin('orders.manage') ?? false, 403);

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['name', 'code', 'sort_order', 'is_active']);
            fclose($output);
        }, strtolower($countryCallingCode->iso_code).'-regions-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** Add/update regions for one country without changing any country/calling-code settings. */
    public function store(Request $request, CountryCallingCode $countryCallingCode, ImportMissingGeography $importer): RedirectResponse
    {
        abort_unless(auth('admin')->user()?->canAdmin('orders.manage') ?? false, 403);

        $file = $request->validate([
            'regions_file' => ['required', 'file', 'mimes:csv,txt,xls,xlsx', 'max:4096'],
        ])['regions_file'];

        try {
            $path = $file->getRealPath();
            $extension = strtolower($file->getClientOriginalExtension());
            if (in_array($extension, ['csv', 'txt'], true)) {
                $reader = new Csv();
                $reader->setInputEncoding('UTF-8');
            } else {
                $reader = IOFactory::createReaderForFile($path);
            }
            $reader->setReadDataOnly(true);
            $workbook = $reader->load($path);
            $sheet = $workbook->getSheetByName('Regions Import') ?? $workbook->getActiveSheet();
            $lastRow = $sheet->getHighestDataRow();
            if ($lastRow > 5001) {
                throw ValidationException::withMessages(['regions_file' => 'A file can contain up to 5,000 region records.']);
            }

            $headers = array_map(
                fn ($value) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $value))),
                $sheet->rangeToArray('A1:D1', null, false, false)[0]
            );
            if ($headers !== ['name', 'code', 'sort_order', 'is_active']) {
                throw ValidationException::withMessages(['regions_file' => 'Use the template columns: name, code, sort_order, is_active.']);
            }

            $rows = $lastRow >= 2 ? $sheet->rangeToArray("A2:D{$lastRow}", null, false, false) : [];
            $regions = [];
            foreach ($rows as $offset => $values) {
                [$name, $code, $sort, $active] = array_map(fn ($value) => trim((string) ($value ?? '')), $values);
                if ($name === '' && $code === '' && $sort === '' && $active === '') {
                    continue;
                }
                if ($name === '' || mb_strlen($name) > 120 || mb_strlen($code) > 20
                    || ($sort !== '' && (! ctype_digit($sort) || (int) $sort > 999999))
                    || ! in_array($active, ['', '0', '1'], true)) {
                    throw ValidationException::withMessages([
                        'regions_file' => 'Invalid data in row '.($offset + 2).'. Check name, code, sort order and active status.',
                    ]);
                }

                // Unique by country + region name. Identical rows in the file are imported once.
                $regions[mb_strtolower($name)] ??= [
                    'iso' => $countryCallingCode->iso_code,
                    'region' => $name,
                    'code' => $code,
                    'sort' => $sort === '' ? 0 : (int) $sort,
                    'regionActive' => $active,
                ];
            }
            if ($regions === []) {
                throw ValidationException::withMessages(['regions_file' => 'The file contains no regions to import.']);
            }

            $country = [
                'iso' => $countryCallingCode->iso_code,
                'name' => $countryCallingCode->country_name,
                'dial' => $countryCallingCode->dial_code,
                'label' => $countryCallingCode->region_label,
                'sort' => $countryCallingCode->sort_order,
                'active' => (bool) $countryCallingCode->is_active,
            ];
            $summary = $importer->import([$country['iso'] => $country], array_values($regions));
            return back()->with('status', "Added {$summary['regions']} regions; filled {$summary['region_codes']} missing region codes for {$countryCallingCode->country_name}. Existing regions were preserved.");
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['regions_file' => 'Could not read the file. Upload CSV, XLS or XLSX using the region template.']);
        }
    }
}
