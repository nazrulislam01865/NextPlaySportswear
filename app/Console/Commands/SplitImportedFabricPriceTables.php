<?php

namespace App\Console\Commands;

use App\Enums\JerseyCustomizationType;
use App\Models\JerseyCustomizationOption;
use App\Models\Product;
use App\Models\ProductOptionGroup;
use App\Models\ProductOptionValue;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SplitImportedFabricPriceTables extends Command
{
    protected $signature = 'products:split-fabric-pricing
        {--product-id=* : One or more Laravel product IDs. Without this option, all products are scanned.}
        {--fabric-column= : Zero-based Fabric column index. Leave empty for automatic detection.}
        {--quantity-column= : Zero-based Quantity column index. Leave empty for automatic detection.}
        {--price-column= : Zero-based Product/unit price column index. Leave empty for automatic detection.}
        {--fabric-type= : Force a JerseyCustomizationType value, for example fabric or quarter_zip_fabric.}
        {--replace : Replace fabric price tables already attached to the product.}
        {--apply : Write changes. Without this option the command only performs a dry run.}';

    protected $description = 'Split an imported combined product price table into separate fabric-specific price tables.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $replace = (bool) $this->option('replace');
        $requestedIds = collect((array) $this->option('product-id'))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $forcedFabricColumn = $this->option('fabric-column');
        $forcedFabricColumn = filled($forcedFabricColumn) ? (int) $forcedFabricColumn : null;
        $forcedQuantityColumn = $this->option('quantity-column');
        $forcedQuantityColumn = filled($forcedQuantityColumn) ? (int) $forcedQuantityColumn : null;
        $forcedPriceColumn = $this->option('price-column');
        $forcedPriceColumn = filled($forcedPriceColumn) ? (int) $forcedPriceColumn : null;
        $forcedFabricType = trim((string) ($this->option('fabric-type') ?? ''));

        if ($forcedFabricType !== '' && ! in_array($forcedFabricType, JerseyCustomizationType::fabricTypeValues(), true)) {
            $this->error('Invalid --fabric-type. Allowed values: '.implode(', ', JerseyCustomizationType::fabricTypeValues()));

            return self::FAILURE;
        }

        $query = Product::query()
            ->whereNotNull('price_table_rows')
            ->orderBy('id');

        if ($requestedIds->isNotEmpty()) {
            $query->whereIn('id', $requestedIds->all());
        }

        $scanned = 0;
        $eligible = 0;
        $changed = 0;
        $skipped = 0;
        $failed = 0;

        $this->newLine();
        $this->info($apply ? 'APPLY MODE: database changes are enabled.' : 'DRY RUN: no database changes will be made.');
        $this->newLine();

        $query->chunkById(50, function (Collection $products) use (
            $apply,
            $replace,
            $forcedFabricColumn,
            $forcedQuantityColumn,
            $forcedPriceColumn,
            $forcedFabricType,
            &$scanned,
            &$eligible,
            &$changed,
            &$skipped,
            &$failed
        ): void {
            foreach ($products as $product) {
                $scanned++;

                try {
                    $analysis = $this->analyzeProduct(
                        $product,
                        $forcedFabricColumn,
                        $forcedQuantityColumn,
                        $forcedPriceColumn,
                        $forcedFabricType
                    );

                    if (! $analysis['eligible']) {
                        $skipped++;

                        if ($this->output->isVerbose()) {
                            $this->line("SKIP #{$product->id} {$product->name}: {$analysis['reason']}");
                        }

                        continue;
                    }

                    $eligible++;
                    $fabricNames = collect($analysis['tables'])->pluck('fabric_label')->implode(', ');
                    $this->line(sprintf(
                        '%s #%d %s | %d fabrics | %s | type=%s',
                        $apply ? 'IMPORT' : 'READY',
                        $product->id,
                        $product->name,
                        count($analysis['tables']),
                        $fabricNames,
                        $analysis['fabric_type']
                    ));

                    if (! $apply) {
                        continue;
                    }

                    if ($product->fabricPriceTables()->exists() && ! $replace) {
                        $this->warn("  Existing fabric price tables found. Skipped; use --replace to overwrite them.");
                        $skipped++;
                        continue;
                    }

                    DB::transaction(function () use ($product, $analysis, $replace): void {
                        $this->applySplit($product, $analysis, $replace);
                    });

                    $changed++;
                    $this->info('  Saved successfully.');
                } catch (Throwable $exception) {
                    $failed++;
                    $this->error("FAILED #{$product->id} {$product->name}: {$exception->getMessage()}");

                    if ($this->output->isVeryVerbose()) {
                        $this->line($exception->getTraceAsString());
                    }
                }
            }
        });

        $this->newLine();
        $this->table(
            ['Scanned', 'Eligible', 'Changed', 'Skipped', 'Failed', 'Mode'],
            [[$scanned, $eligible, $changed, $skipped, $failed, $apply ? 'APPLY' : 'DRY RUN']]
        );

        if (! $apply && $eligible > 0) {
            $this->comment('Review the READY products, then rerun with --apply.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{
     *   eligible: bool,
     *   reason: string,
     *   fabric_column: int|null,
     *   quantity_column: int|null,
     *   price_column: int|null,
     *   output_headers: array<int,string>,
     *   output_highlight_column: int,
     *   fabric_type: string,
     *   existing_group: ProductOptionGroup|null,
     *   tables: array<int,array<string,mixed>>
     * }
     */
    private function analyzeProduct(
        Product $product,
        ?int $forcedFabricColumn,
        ?int $forcedQuantityColumn,
        ?int $forcedPriceColumn,
        string $forcedFabricType
    ): array
    {
        $headers = $this->asArray($product->price_table_headers);
        $rows = collect($this->asArray($product->price_table_rows))
            ->filter(fn ($row) => is_array($row))
            ->map(fn ($row) => array_values($row))
            ->values();

        if ($headers === [] || $rows->isEmpty()) {
            return $this->ineligible('No imported price table headers or rows.');
        }

        $fabricColumn = $forcedFabricColumn ?? $this->detectFabricColumn($headers, $rows);
        if ($fabricColumn === null || $fabricColumn < 0 || $fabricColumn >= count($headers)) {
            return $this->ineligible('Fabric column could not be detected. Use --fabric-column=1 when Fabric is the second column.');
        }

        $quantityColumn = $forcedQuantityColumn ?? $this->detectQuantityColumn($headers);
        $priceColumn = $forcedPriceColumn ?? $this->detectPriceColumn($headers, $product->price_table_highlight_column, $fabricColumn);

        if ($quantityColumn !== null && ($quantityColumn < 0 || $quantityColumn >= count($headers))) {
            return $this->ineligible('The forced Quantity column is outside the table header range.');
        }

        if ($priceColumn !== null && ($priceColumn < 0 || $priceColumn >= count($headers) || $priceColumn === $fabricColumn)) {
            return $this->ineligible('The forced Product/unit price column is invalid or points to the Fabric column.');
        }

        if ($quantityColumn === null) {
            return $this->ineligible('Quantity column could not be detected.');
        }

        if ($priceColumn === null) {
            return $this->ineligible('Product/unit price column could not be detected.');
        }

        $grouped = $rows
            ->filter(fn (array $row) => filled($row[$fabricColumn] ?? null))
            ->groupBy(fn (array $row) => $this->cleanFabricLabel((string) ($row[$fabricColumn] ?? '')));

        if ($grouped->count() < 2) {
            return $this->ineligible('The table does not contain at least two distinct fabric names.');
        }

        $outputHeaders = collect($headers)
            ->reject(fn ($header, int $index) => $index === $fabricColumn)
            ->map(fn ($header) => trim((string) $header))
            ->values()
            ->all();

        $outputQuantityColumn = $this->shiftedIndex($quantityColumn, $fabricColumn);
        $outputPriceColumn = $this->shiftedIndex($priceColumn, $fabricColumn);

        if ($outputQuantityColumn !== 0) {
            return $this->ineligible('Quantity must remain the first output column for the current project pricing structure.');
        }

        $existingGroup = $this->bestExistingFabricGroup($product, $grouped->keys());
        $fabricType = $forcedFabricType !== ''
            ? $forcedFabricType
            : ($existingGroup?->jersey_customization_type ?: $this->inferFabricType($product));

        $tables = [];

        foreach ($grouped as $fabricLabel => $fabricRows) {
            $normalizedRows = $this->normalizeFabricRows(
                rows: $fabricRows->values(),
                fabricColumn: $fabricColumn,
                quantityColumn: $quantityColumn,
                priceColumn: $priceColumn,
                outputPriceColumn: $outputPriceColumn
            );

            if ($normalizedRows['tiers'] === []) {
                throw new RuntimeException("No numeric product prices were found for fabric: {$fabricLabel}");
            }

            $tables[] = [
                'fabric_label' => $fabricLabel,
                'fabric_slug' => Str::slug($fabricLabel),
                'headers' => $outputHeaders,
                'rows' => $normalizedRows['rows'],
                'ranges' => $normalizedRows['ranges'],
                'tiers' => $normalizedRows['tiers'],
                'highlight_column' => $outputPriceColumn,
            ];
        }

        return [
            'eligible' => true,
            'reason' => '',
            'fabric_column' => $fabricColumn,
            'quantity_column' => $quantityColumn,
            'price_column' => $priceColumn,
            'output_headers' => $outputHeaders,
            'output_highlight_column' => $outputPriceColumn,
            'fabric_type' => $fabricType,
            'existing_group' => $existingGroup,
            'tables' => $tables,
        ];
    }

    /** @return array<string,mixed> */
    private function ineligible(string $reason): array
    {
        return [
            'eligible' => false,
            'reason' => $reason,
            'fabric_column' => null,
            'quantity_column' => null,
            'price_column' => null,
            'output_headers' => [],
            'output_highlight_column' => 1,
            'fabric_type' => JerseyCustomizationType::Fabric->value,
            'existing_group' => null,
            'tables' => [],
        ];
    }

    /** @param array<string,mixed> $analysis */
    private function applySplit(Product $product, array $analysis, bool $replace): void
    {
        if ($replace) {
            $product->fabricPriceTables()->delete();
        }

        /** @var ProductOptionGroup|null $group */
        $group = $analysis['existing_group'];
        $fabricType = (string) $analysis['fabric_type'];

        if (! $group) {
            $enum = JerseyCustomizationType::from($fabricType);
            $preferredCode = $fabricType;
            $code = $preferredCode;
            $counter = 2;

            while ($product->optionGroups()->where('code', $code)->exists()) {
                $code = $preferredCode.'-'.$counter;
                $counter++;
            }

            $group = $product->optionGroups()->create([
                'name' => $enum->label(),
                'code' => $code,
                'section' => 'product',
                'type' => 'select',
                'jersey_customization_type' => $fabricType,
                'display_mode' => 'customer',
                'show_in_summary' => true,
                'use_as_filter' => false,
                'description' => null,
                'placeholder' => 'Select fabric',
                'is_required' => true,
                'minimum_selections' => 1,
                'maximum_selections' => 1,
                'is_active' => true,
                'sort_order' => (int) $product->optionGroups()->max('sort_order') + 1,
            ]);
        } else {
            $group->update([
                'jersey_customization_type' => $fabricType,
                'type' => 'select',
                'display_mode' => 'customer',
                'show_in_summary' => true,
                'is_required' => true,
                'minimum_selections' => 1,
                'maximum_selections' => 1,
                'is_active' => true,
            ]);
        }

        $group->values()->update(['is_default' => false]);

        $defaultTableData = null;

        foreach ($analysis['tables'] as $sortOrder => $tableData) {
            $fabricLabel = (string) $tableData['fabric_label'];
            $fabricSlug = (string) $tableData['fabric_slug'];

            $existingValue = $group->values()
                ->get()
                ->first(function (ProductOptionValue $value) use ($fabricSlug, $fabricLabel): bool {
                    return $this->canonicalFabricKey((string) $value->code) === $this->canonicalFabricKey($fabricLabel)
                        || $this->canonicalFabricKey((string) $value->label) === $this->canonicalFabricKey($fabricLabel);
                });

            $masterFabric = null;

            if ($existingValue?->jersey_customization_option_id) {
                $masterFabric = JerseyCustomizationOption::query()
                    ->whereKey($existingValue->jersey_customization_option_id)
                    ->where('type', $fabricType)
                    ->first();
            }

            $masterFabric ??= JerseyCustomizationOption::query()
                ->where('type', $fabricType)
                ->get()
                ->first(fn (JerseyCustomizationOption $option): bool =>
                    $this->canonicalFabricKey((string) $option->name) === $this->canonicalFabricKey($fabricLabel)
                    || $this->canonicalFabricKey((string) $option->slug) === $this->canonicalFabricKey($fabricLabel)
                );

            $masterFabric ??= JerseyCustomizationOption::query()->firstOrCreate(
                [
                    'type' => $fabricType,
                    'slug' => $fabricSlug,
                ],
                [
                    'name' => $fabricLabel,
                    'description' => null,
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]
            );

            $valueCode = $existingValue?->code ?: (string) $masterFabric->slug;

            if ($existingValue) {
                $existingValue->update([
                    'jersey_customization_option_id' => $masterFabric->id,
                    'label' => $fabricLabel,
                    'price_adjustment' => 0,
                    'charge_type' => 'per_unit',
                    'is_default' => $sortOrder === 0,
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]);
                $optionValue = $existingValue;
            } else {
                $optionValue = $group->values()->create([
                    'jersey_customization_option_id' => $masterFabric->id,
                    'label' => $fabricLabel,
                    'code' => $valueCode,
                    'description' => null,
                    'price_adjustment' => 0,
                    'charge_type' => 'per_unit',
                    'is_default' => $sortOrder === 0,
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]);
            }

            $fabricKey = 'master:'.$masterFabric->id;

            $fabricTable = $product->fabricPriceTables()->updateOrCreate(
                ['fabric_key' => $fabricKey],
                [
                    'jersey_customization_option_id' => $masterFabric->id,
                    'fabric_code' => (string) $optionValue->code,
                    'fabric_label' => $fabricLabel,
                    'price_table_headers' => $tableData['headers'],
                    'price_table_rows' => $tableData['rows'],
                    'price_table_ranges' => $tableData['ranges'],
                    'price_table_highlight_column' => $tableData['highlight_column'],
                    'price_table_note' => $product->price_table_note,
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]
            );

            $fabricTable->tiers()->delete();

            foreach ($tableData['tiers'] as $tierSortOrder => $tier) {
                $fabricTable->tiers()->create(array_merge($tier, [
                    'sort_order' => $tierSortOrder,
                ]));
            }

            if ($sortOrder === 0) {
                $defaultTableData = $tableData;
            }
        }

        if (! $defaultTableData) {
            throw new RuntimeException('No default fabric price table was generated.');
        }

        $firstTier = $defaultTableData['tiers'][0] ?? null;
        if (! $firstTier) {
            throw new RuntimeException('The default fabric does not have a valid first price tier.');
        }

        $product->update([
            'price_table_headers' => $defaultTableData['headers'],
            'price_table_rows' => $defaultTableData['rows'],
            'price_table_highlight_column' => $defaultTableData['highlight_column'],
            'base_price' => $firstTier['unit_price'],
            'minimum_quantity' => $firstTier['minimum_quantity'],
        ]);

        $product->priceTiers()->delete();

        foreach ($defaultTableData['tiers'] as $tierSortOrder => $tier) {
            $product->priceTiers()->create(array_merge($tier, [
                'sort_order' => $tierSortOrder,
            ]));
        }
    }

    /** @param array<int,string> $headers */
    private function detectQuantityColumn(array $headers): ?int
    {
        foreach ($headers as $index => $header) {
            $normalized = Str::lower(trim((string) $header));
            if ($normalized === 'quantity' || $normalized === 'qty' || str_contains($normalized, 'quantity')) {
                return $index;
            }
        }

        return isset($headers[0]) ? 0 : null;
    }

    /**
     * @param array<int,string> $headers
     * @param Collection<int,array<int,mixed>> $rows
     */
    private function detectFabricColumn(array $headers, Collection $rows): ?int
    {
        foreach ($headers as $index => $header) {
            $normalized = Str::lower(trim((string) $header));
            if (str_contains($normalized, 'fabric') || str_contains($normalized, 'material')) {
                return $index;
            }
        }

        foreach (range(0, max(count($headers) - 1, 0)) as $index) {
            $labels = $rows
                ->pluck($index)
                ->filter(fn ($value) => filled($value))
                ->map(fn ($value) => $this->normalizeLabel((string) $value))
                ->unique()
                ->values();

            if ($labels->count() >= 2 && $labels->every(fn (string $label) => $this->looksLikeFabricLabel($label))) {
                return $index;
            }
        }

        return null;
    }

    /** @param array<int,string> $headers */
    private function detectPriceColumn(array $headers, mixed $configuredHighlight, int $fabricColumn): ?int
    {
        $configured = is_numeric($configuredHighlight) ? (int) $configuredHighlight : null;
        if ($configured !== null && isset($headers[$configured]) && $configured !== $fabricColumn) {
            $configuredHeader = Str::lower(trim((string) $headers[$configured]));
            if (! str_contains($configuredHeader, 'shipping') && ! str_contains($configuredHeader, 'freight')) {
                return $configured;
            }
        }

        foreach ($headers as $index => $header) {
            if ($index === $fabricColumn) {
                continue;
            }

            $normalized = Str::lower(trim((string) $header));
            $isPrice = str_contains($normalized, 'product price')
                || str_contains($normalized, 'unit price')
                || $normalized === 'price'
                || str_contains($normalized, 'price per')
                || (str_contains($normalized, 'price')
                    && ! str_contains($normalized, 'shipping')
                    && ! str_contains($normalized, 'freight')
                    && ! str_contains($normalized, 'surcharge')
                    && ! str_contains($normalized, 'total'));
            $isShipping = str_contains($normalized, 'shipping')
                || str_contains($normalized, 'freight')
                || str_contains($normalized, 'surcharge')
                || str_contains($normalized, 'total');

            if ($isPrice && ! $isShipping) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param Collection<int,array<int,mixed>> $rows
     * @return array{rows:array<int,array<int,string>>,ranges:array<int,array<string,int|null>>,tiers:array<int,array<string,mixed>>}
     */
    private function normalizeFabricRows(
        Collection $rows,
        int $fabricColumn,
        int $quantityColumn,
        int $priceColumn,
        int $outputPriceColumn
    ): array {
        $parsed = $rows
            ->map(function (array $row) use ($fabricColumn, $quantityColumn, $priceColumn): ?array {
                $range = $this->parseQuantityRange((string) ($row[$quantityColumn] ?? ''));
                $unitPrice = $this->parseMoney($row[$priceColumn] ?? null);

                if ($range['minimum_quantity'] < 1 || $unitPrice === null) {
                    return null;
                }

                $outputRow = collect($row)
                    ->reject(fn ($value, int $index) => $index === $fabricColumn)
                    ->map(fn ($value) => trim((string) $value))
                    ->values()
                    ->all();

                return [
                    'minimum_quantity' => $range['minimum_quantity'],
                    'maximum_quantity' => $range['maximum_quantity'],
                    'unit_price' => $unitPrice,
                    'output_row' => $outputRow,
                ];
            })
            ->filter()
            ->sortBy('minimum_quantity')
            ->values();

        $outputRows = [];
        $ranges = [];
        $tiers = [];

        foreach ($parsed as $index => $item) {
            $minimum = (int) $item['minimum_quantity'];
            $maximum = $item['maximum_quantity'];
            $nextMinimum = isset($parsed[$index + 1]) ? (int) $parsed[$index + 1]['minimum_quantity'] : null;

            if ($maximum === null && $nextMinimum !== null) {
                $maximum = max($minimum, $nextMinimum - 1);
            }

            $label = $this->quantityLabel($minimum, $maximum);
            $displayRow = $item['output_row'];
            $displayRow[0] = $label;

            $outputRows[] = array_values($displayRow);
            $ranges[] = [
                'minimum_quantity' => $minimum,
                'maximum_quantity' => $maximum,
            ];
            $tiers[] = [
                'label' => $label,
                'minimum_quantity' => $minimum,
                'maximum_quantity' => $maximum,
                'unit_price' => $item['unit_price'],
                'compare_at_price' => null,
                'savings_label' => null,
            ];
        }

        return [
            'rows' => $outputRows,
            'ranges' => $ranges,
            'tiers' => $tiers,
        ];
    }

    /** @param Collection<int,string> $fabricLabels */
    private function bestExistingFabricGroup(Product $product, Collection $fabricLabels): ?ProductOptionGroup
    {
        $groups = $product->optionGroups()
            ->where(function ($query): void {
                $query->whereIn('jersey_customization_type', JerseyCustomizationType::fabricTypeValues())
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%fabric%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%material%'])
                    ->orWhereRaw('LOWER(code) LIKE ?', ['%fabric%'])
                    ->orWhereRaw('LOWER(code) LIKE ?', ['%material%']);
            })
            ->with('values')
            ->get();

        if ($groups->isEmpty()) {
            return null;
        }

        $wanted = $fabricLabels->map(fn ($label) => $this->canonicalFabricKey((string) $label))->unique();

        return $groups
            ->sortByDesc(function (ProductOptionGroup $group) use ($wanted): int {
                return $group->values
                    ->filter(fn (ProductOptionValue $value) => $wanted->contains($this->canonicalFabricKey((string) $value->label)))
                    ->count();
            })
            ->first();
    }

    private function inferFabricType(Product $product): string
    {
        $profile = Str::lower(trim((string) ($product->product_profile ?: $product->product_type ?: '')));

        return match (true) {
            str_contains($profile, 'quarter') => JerseyCustomizationType::QuarterZipFabric->value,
            str_contains($profile, 'tshirt'), str_contains($profile, 't-shirt'), str_contains($profile, 'tee') => JerseyCustomizationType::TshirtFabric->value,
            $profile === 'shorts', str_contains($profile, 'shorts_'), str_contains($profile, 'shorts-') => JerseyCustomizationType::ShortsFabric->value,
            str_contains($profile, 'pant') => JerseyCustomizationType::PantsFabric->value,
            str_contains($profile, 'hood') => JerseyCustomizationType::HoodieFabric->value,
            str_contains($profile, 'polo') => JerseyCustomizationType::PoloFabric->value,
            str_contains($profile, 'tank') => JerseyCustomizationType::TankTopFabric->value,
            str_contains($profile, 'compression') => JerseyCustomizationType::CompressionWearMaterials->value,
            str_contains($profile, 'sock') => JerseyCustomizationType::SocksMaterialConstruction->value,
            str_contains($profile, 'sweatshirt') => JerseyCustomizationType::SweatshirtFabric->value,
            str_contains($profile, 'jacket') => JerseyCustomizationType::JacketOuterFabric->value,
            default => JerseyCustomizationType::Fabric->value,
        };
    }

    /** @return array{minimum_quantity:int,maximum_quantity:int|null} */
    private function parseQuantityRange(string $value): array
    {
        $normalized = str_replace([',', '–', '—'], ['', '-', '-'], trim($value));
        preg_match_all('/\d+/', $normalized, $matches);
        $numbers = collect($matches[0] ?? [])->map(fn ($number) => (int) $number)->values();

        return [
            'minimum_quantity' => (int) ($numbers->get(0) ?: 0),
            'maximum_quantity' => str_contains($normalized, '+') ? null : $numbers->get(1),
        ];
    }

    private function parseMoney(mixed $value): ?float
    {
        $clean = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', (string) $value));

        if ($clean === '' || $clean === '-' || $clean === '.' || ! is_numeric($clean)) {
            return null;
        }

        return round((float) $clean, 2);
    }

    private function quantityLabel(int $minimum, ?int $maximum): string
    {
        return $maximum !== null ? $minimum.'-'.$maximum : $minimum.'+';
    }

    private function shiftedIndex(int $originalIndex, int $removedIndex): int
    {
        return $originalIndex > $removedIndex ? $originalIndex - 1 : $originalIndex;
    }

    private function normalizeLabel(string $label): string
    {
        $label = html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $label = preg_replace('/\s+/u', ' ', $label) ?: $label;

        return trim($label);
    }

    private function cleanFabricLabel(string $label): string
    {
        $label = $this->normalizeLabel($label);

        if (preg_match('/pricing\s+table\s*\((.*?)\)/iu', $label, $matches)) {
            $label = trim((string) ($matches[1] ?? $label));
        }

        $label = preg_replace('/^fabric\s+option(?:\s+\d+)?\s*:\s*/iu', '', $label) ?: $label;
        $label = preg_replace('/\s*\(\s*fabric\s+code\s*:[^)]+\)\s*/iu', '', $label) ?: $label;
        $label = preg_replace('/\s*\/\s*100\s*%\s*polyester\s*$/iu', '', $label) ?: $label;
        $label = preg_replace('/\s+pricing\s*(?:\(\s*(?:usd|100\s*%\s*polyester)\s*\))?\s*$/iu', '', $label) ?: $label;
        $label = preg_replace('/\s*[–—-]\s*usd\s*$/iu', '', $label) ?: $label;
        $label = preg_replace('/(\d+)\s+gsm\b/iu', '$1gsm', $label) ?: $label;

        return $this->normalizeLabel($label);
    }

    private function canonicalFabricKey(string $label): string
    {
        $label = Str::lower($this->cleanFabricLabel($label));
        $label = preg_replace('/(\d+)\s*g(?:sm)?\b/iu', '$1gsm', $label) ?: $label;
        $label = preg_replace('/\b(?:fabric|material)\b/iu', ' ', $label) ?: $label;
        $label = preg_replace('/\b100\s*%\s*polyester\b/iu', ' ', $label) ?: $label;
        $label = preg_replace('/[^a-z0-9]+/iu', '-', $label) ?: $label;

        return trim($label, '-');
    }

    private function looksLikeFabricLabel(string $label): bool
    {
        return (bool) preg_match(
            '/\b(fabric|mesh|jacquard|woven|roving|polyester|cotton|silk|birdseye|pinhole|butterfly|nba|gsm|material)\b/i',
            $label
        );
    }

    /** @return array<int,mixed> */
    private function asArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? array_values($decoded) : [];
        }

        return [];
    }
}
