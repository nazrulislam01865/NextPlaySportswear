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

class CountryGeographyImportController extends Controller
{
    public function template(): StreamedResponse
    {
        abort_unless(auth('admin')->user()?->canAdmin('orders.manage') ?? false, 403);
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['iso_code', 'country_name', 'dial_code', 'region_label', 'region_name', 'region_code', 'sort_order', 'country_active', 'region_active']);
            fputcsv($out, ['GB', 'United Kingdom', '+44', 'County', 'Greater London', 'LND', '10', '1', '1']);
            fputcsv($out, ['CA', 'Canada', '+1', 'Province', 'Ontario', 'ON', '10', '1', '1']);
            fclose($out);
        }, 'country-regions-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(Request $request, ImportMissingGeography $importer): RedirectResponse
    {
        abort_unless(auth('admin')->user()?->canAdmin('orders.manage') ?? false, 403);
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:4096'],
        ]);
        $file = $data['file'];
        $path = $file->getRealPath();
        try {
            $extension = strtolower($file->getClientOriginalExtension());
            if (in_array($extension, ['csv', 'txt'], true)) {
                $reader = new Csv();
                $reader->setInputEncoding('UTF-8');
            } else {
                $reader = IOFactory::createReaderForFile($path);
            }
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($path)->getActiveSheet();
            $lastRow = $sheet->getHighestDataRow();
            if ($lastRow > 5001) {
                throw ValidationException::withMessages(['file' => 'Import supports up to 5,000 data rows per file.']);
            }
            $headings = array_map(fn ($v) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $v))),
                $sheet->rangeToArray('A1:I1', null, false, false)[0]);
            if ($headings !== ['iso_code', 'country_name', 'dial_code', 'region_label', 'region_name', 'region_code', 'sort_order', 'country_active', 'region_active']) {
                throw ValidationException::withMessages(['file' => 'Column headings do not match the downloadable template.']);
            }

            $existing = CountryCallingCode::query()->get()->keyBy('iso_code');
            $rows = $lastRow >= 2 ? $sheet->rangeToArray("A2:I{$lastRow}", null, false, false) : [];
            $countries = [];
            $regions = [];
            foreach ($rows as $offset => $raw) {
                $number = $offset + 2;
                $cells = array_map(fn ($v) => trim((string) ($v ?? '')), $raw);
                if (implode('', $cells) === '') continue;
                [$iso, $name, $dial, $label, $region, $code, $sort, $active, $regionActive] = $cells;
                $iso = strtoupper($iso);
                $record = $existing->get($iso);
                $name = $name ?: ($countries[$iso]['name'] ?? $record?->country_name ?? '');
                $dial = $dial ?: ($countries[$iso]['dial'] ?? $record?->dial_code ?? '');
                $label = $label ?: ($countries[$iso]['label'] ?? $record?->region_label ?? 'State / Province');
                $sort = $sort === '' ? ($countries[$iso]['sort'] ?? $record?->sort_order ?? 0) : $sort;
                $active = $active === '' ? (string) (int) ($countries[$iso]['active'] ?? $record?->is_active ?? true) : $active;
                $valid = preg_match('/^[A-Z]{2}$/', $iso) && mb_strlen($name) <= 120 && $name !== ''
                    && preg_match('/^\+[0-9]{1,10}$/', $dial)
                    && mb_strlen($label) <= 30 && $label !== ''
                    && mb_strlen($region) <= 120 && mb_strlen($code) <= 20
                    && ctype_digit((string) $sort) && (int) $sort <= 999999
                    && in_array((string) $active, ['1', '0'], true)
                    && in_array((string) $regionActive, ['', '1', '0'], true);
                if (! $valid) {
                    throw ValidationException::withMessages(['file' => "Invalid data in row {$number}. Check codes, country name, labels, numbers and status."]);
                }
                $countries[$iso] ??= compact('iso', 'name', 'dial', 'label', 'sort', 'active');
                if ($region !== '') $regions[$iso.'|'.mb_strtolower($region)] ??= compact('iso', 'region', 'code', 'sort', 'regionActive');
            }
            if ($countries === []) {
                throw ValidationException::withMessages(['file' => 'The import file contains no records.']);
            }
            $summary = $importer->import($countries, array_values($regions));
            return back()->with('status', "Added {$summary['countries']} countries and {$summary['regions']} regions; filled {$summary['country_fields']} country records and {$summary['region_codes']} missing region codes. Existing data was preserved.");
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['file' => 'Could not read the file. Use the template in CSV, XLS or XLSX format.']);
        }
    }
}
