<?php

namespace App\Services\BulkQuote;

use App\Models\BulkQuoteRequest;
use App\Models\Product;
use App\Support\PriceTableShipping;
use App\Services\Shipping\RuralAreaSurchargeService;
use Illuminate\Validation\ValidationException;

/** The quotation tab's product catalog snapshot and server-authoritative totals. */
final class BulkQuoteQuotationEditor
{
    public function __construct(private readonly RuralAreaSurchargeService $remoteAreas) {}

    public function prepare(BulkQuoteRequest $quote): array
    {
        $information = (array) ($quote->product_information ?: []);
        $items = $information['selected_products'] ?? null;
        if (! is_array($items) || $items === []) {
            $items = [[
                'name' => $information['product_name'] ?? ($quote->product_type ?: 'Custom product'),
                'product_id' => $information['product_id'] ?? null,
                'sku' => $information['sku'] ?? null,
                'quantity' => is_numeric($quote->estimated_quantity) ? (int) $quote->estimated_quantity : 1,
                'options' => $information['options'] ?? [],
                'size_groups' => $information['size_groups'] ?? [],
            ]];
        }
        // This is deliberately a bounded, fixed number of relations, not a product query for every row.
        $ids = collect($items)->pluck('product_id')->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)->unique()->values();
        $catalog = $ids->isEmpty() ? collect() : Product::query()
            ->with(['priceTiers', 'optionGroups.values', 'fabricPriceTables.tiers', 'shippingMethods.shippingMethod'])
            ->whereIn('id', $ids)->get()->keyBy('id');
        $draft = (array) ($quote->quotation_draft ?: []);
        $saved = (array) ($draft['lines'] ?? ($quote->quotation_details['pricing_lines'] ?? []));
        $lines = [];
        foreach (array_slice(array_values($items), 0, 50) as $index => $item) {
            if (! is_array($item)) continue;
            $product = $catalog->get((int) ($item['product_id'] ?? 0));
            $groups = $product ? $this->groups($product, (array) ($item['options'] ?? [])) : [];
            $tiers = $product?->priceTiers->map(fn ($tier) => [
                'min' => (int) $tier->minimum_quantity,
                'max' => $tier->maximum_quantity === null ? null : (int) $tier->maximum_quantity,
                'unit' => (float) $tier->unit_price,
            ])->values()->all() ?? [];
            $fabrics = $product?->fabricPriceTables->where('is_active', true)->map(fn ($table) => [
                'id' => (string) $table->id,
                'label' => (string) ($table->fabric_label ?: $table->fabric_code),
                'headers' => (array) $table->price_table_headers,
                'rows' => (array) $table->price_table_rows,
                'tiers' => $table->tiers->map(fn ($tier) => [
                    'min' => (int) $tier->minimum_quantity,
                    'max' => $tier->maximum_quantity === null ? null : (int) $tier->maximum_quantity,
                    'unit' => (float) $tier->unit_price,
                ])->values()->all(),
            ])->values()->all() ?? [];
            $qty = max(1, min(10000000, (int) ($item['quantity'] ?? 1)));
            $rates = $this->shippingRates($product, $qty);
            $selectedFabric = collect((array) ($item['options'] ?? []))
                ->first(fn ($option) => is_array($option) && preg_match('/fabric|material/i', (string) ($option['label'] ?? '')) === 1);
            $fabricTable = collect($fabrics)->first(fn ($table) => is_array($selectedFabric)
                && mb_strtolower(trim((string) $table['label'])) === mb_strtolower(trim((string) ($selectedFabric['value'] ?? ''))));
            $activeTiers = (array) ($fabricTable['tiers'] ?? $tiers);
            $baseUnit = $this->tierPrice($activeTiers, $qty, (float) ($product?->base_price ?? 0));
            $unitTable = $this->tableUnit($fabricTable['headers'] ?? $product?->price_table_headers ?? [],
                $fabricTable['rows'] ?? $product?->price_table_rows ?? [], $activeTiers, $qty);
            $savedRow = (array) ($saved[$index] ?? []);
            // Show already-selected catalogue groups before groups the customer left unselected.
            // A saved admin draft takes precedence over the customer's original selection.
            $savedSelections = (array) ($savedRow['options'] ?? []);
            $groups = collect($groups)->sortBy(static function (array $group) use ($savedSelections): int {
                $selected = $savedSelections[$group['id']] ?? $group['selected'];
                return count(array_filter((array) $selected, static fn ($id) => (string) $id !== '')) ? 0 : 1;
            })->values()->all();
            $lines[] = [
                'index' => $index,
                'product_id' => $product?->id,
                'name' => (string) ($item['name'] ?? $product?->name ?? 'Custom product'),
                'sku' => (string) ($item['sku'] ?? $product?->sku ?? ''),
                'sizes' => (string) ($item['sizes'] ?? ''),
                'selected_options' => (array) ($item['options'] ?? []),
                'groups' => $groups,
                'tiers' => $tiers,
                'fabric_tiers' => $fabrics,
                'table_headers' => (array) ($product?->price_table_headers ?? []),
                'table_rows' => (array) ($product?->price_table_rows ?? []),
                'remote_table_rate' => $product ? PriceTableShipping::remoteSurcharge(
                    (array) ($product->price_table_headers ?? []), (array) ($product->price_table_rows ?? []),
                    $product->priceTiers->toArray(), $qty
                ) : null,
                'base_price' => (float) ($product?->base_price ?? 0),
                'sample_default' => (float) ($product?->sample_charge ?? $product?->base_price ?? 0),
                'quantity' => $qty,
                'unit_price' => $activeTiers !== [] ? $baseUnit : ($unitTable ?? $baseUnit),
                'shipping_rates' => $rates,
                'draft' => $savedRow,
            ];
        }
        $remoteCharge = 0.0;
        if ($quote->is_remote_area) {
            if (filled($quote->postal_code)) {
                $match = $this->remoteAreas->resolve($quote->postal_code, $quote->country, $quote->state_province);
                $remoteCharge = (float) ($match['extra_charge'] ?? 0);
            }
            // Product-tier remote freight (when explicitly configured) wins over a generic postal fee.
            $configuredRates = array_filter(array_column($lines, 'remote_table_rate'), fn ($rate) => $rate !== null);
            if ($configuredRates !== []) $remoteCharge = max(array_map('floatval', $configuredRates));
        }
        return [
            'lines' => $lines,
            'draft' => $draft,
            'remote' => (bool) $quote->is_remote_area,
            'remote_charge' => round($remoteCharge, 2),
            'delivery' => [
                'address' => $quote->shipping_address ?: '',
                'postal' => $quote->postal_code ?: '',
                'country' => $quote->country ?: '',
                'state' => $quote->state_province ?: '',
                'preferred' => in_array($quote->preferred_shipping_method, ['express', 'rush'], true) ? 'express' : 'regular',
            ],
        ];
    }

    /** @return array{quantity:int,subtotal:float,customization:float,shipping:float,shipping_method:string,pricing_lines:array,sample:array,pricing_breakdown:array} */
    public function calculate(BulkQuoteRequest $quote, array $input, bool $draft = false): array
    {
        $config = $this->prepare($quote);
        $posted = $input['lines'] ?? [];
        if (! is_array($posted) || count($posted) !== count($config['lines'])) {
            throw ValidationException::withMessages(['lines' => 'The quotation lines have changed. Reload this request to continue.']);
        }
        $method = (string) ($input['shipping_method'] ?? $config['delivery']['preferred']);
        $sampleMethod = (string) ($input['sample_shipping_method'] ?? 'regular');
        if (! in_array($method, ['regular', 'express'], true) || ! in_array($sampleMethod, ['regular', 'express'], true)) {
            throw ValidationException::withMessages(['shipping_method' => 'Select a valid shipping method.']);
        }
        $sampleEnabled = filter_var($input['sample_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $subtotal = $optionTotal = $imprintTotal = $shipping = $sampleProducts = $sampleCustomization = $sampleShipping = 0.0;
        $qtyTotal = 0;
        $hasSampleShipment = false;
        $normalized = [];
        foreach ($config['lines'] as $index => $row) {
            $line = (array) ($posted[$index] ?? []);
            $qty = $this->integer($line['quantity'] ?? null, "lines.$index.quantity", 1, 10000000);
            $qtyTotal += $qty;
            $unit = $this->money($line['unit_price'] ?? null, "lines.$index.unit_price");
            $imprintPerUnit = $this->money($line['imprint_unit'] ?? 0, "lines.$index.imprint_unit");
            $setup = $this->money($line['imprint_setup'] ?? 0, "lines.$index.imprint_setup");
            $reg = $this->money($line['regular_shipping'] ?? 0, "lines.$index.regular_shipping");
            $exp = $this->money($line['express_shipping'] ?? 0, "lines.$index.express_shipping");
            // Bulk and printed samples can have different configured option choices.
            // Older API clients that do not send sample_options keep the bulk choice.
            $selections = (array) ($line['options'] ?? []);
            [$validatedOptions, $optionPerUnit, $optionFixed] = $this->resolveOptions(
                $row['groups'], $selections, "lines.$index.options", ! $draft, $row['name']
            );
            $subtotal += $qty * $unit;
            $optionTotal += $qty * $optionPerUnit + $optionFixed;
            $imprintTotal += $qty * $imprintPerUnit + $setup;
            $shipping += $method === 'regular' ? $reg : $exp;
            $sampleRequestedQty = $this->integer($line['sample_quantity'] ?? 0, "lines.$index.sample_quantity", 0, 1000);
            $sampleQty = $sampleEnabled ? $sampleRequestedQty : 0;
            $samplePrice = $this->money($line['sample_unit'] ?? 0, "lines.$index.sample_unit");
            $sampleType = (string) ($line['sample_type'] ?? 'printed');
            if (! in_array($sampleType, ['printed', 'blank'], true)) throw ValidationException::withMessages(["lines.$index.sample_type" => 'Select a valid sample type.']);
            $sampleChoices = array_key_exists('sample_options', $line) ? (array) $line['sample_options'] : $validatedOptions;
            [$validatedSampleOptions, $sampleOptionUnit, $sampleOptionFixed] = $this->resolveOptions(
                $row['groups'], $sampleChoices, "lines.$index.sample_options",
                ! $draft && $sampleEnabled && $sampleQty > 0 && $sampleType === 'printed',
                $row['name']
            );
            $sampleImprint = $this->money($line['sample_imprint_unit'] ?? 0, "lines.$index.sample_imprint_unit");
            $sampleSetup = $this->money($line['sample_setup'] ?? 0, "lines.$index.sample_setup");
            $sampleReg = $this->money($line['sample_regular_shipping'] ?? 0, "lines.$index.sample_regular_shipping");
            $sampleExp = $this->money($line['sample_express_shipping'] ?? 0, "lines.$index.sample_express_shipping");
            if ($sampleEnabled && $sampleQty) {
                $hasSampleShipment = true;
                $sampleProducts += $sampleQty * $samplePrice;
                if ($sampleType === 'printed') {
                    $sampleCustomization += $sampleQty * ($sampleImprint + $sampleOptionUnit) + $sampleSetup + $sampleOptionFixed;
                }
                $sampleShipping += $sampleMethod === 'regular' ? $sampleReg : $sampleExp;
            }
            $normalized[] = [
                'name' => $row['name'], 'sku' => $row['sku'], 'product_id' => $row['product_id'],
                'quantity' => $qty, 'unit_price' => $unit, 'options' => $validatedOptions,
                'option_per_unit' => round($optionPerUnit, 2), 'option_fixed' => round($optionFixed, 2),
                'imprint_unit' => $imprintPerUnit, 'imprint_setup' => $setup,
                'regular_shipping' => $reg, 'express_shipping' => $exp,
                'sample_quantity' => $sampleRequestedQty, 'sample_unit' => $samplePrice, 'sample_type' => $sampleType,
                'sample_options' => $validatedSampleOptions, 'sample_option_per_unit' => round($sampleOptionUnit, 2), 'sample_option_fixed' => round($sampleOptionFixed, 2),
                'sample_imprint_unit' => $sampleImprint, 'sample_setup' => $sampleSetup,
                'sample_regular_shipping' => $sampleReg, 'sample_express_shipping' => $sampleExp,
            ];
        }
        if ($qtyTotal > 10000000) {
            throw ValidationException::withMessages(['lines' => 'The combined quotation quantity cannot exceed 10,000,000 pieces.']);
        }
        $private = $this->money($input['private_address_charge'] ?? 0, 'private_address_charge');
        $remote = $config['remote'] ? $this->money($input['remote_charge'] ?? $config['remote_charge'], 'remote_charge') : 0.0;
        $sampleRemote = $config['remote'] && $sampleEnabled && $hasSampleShipment
            ? $this->money($input['sample_remote_charge'] ?? 0, 'sample_remote_charge') : 0.0;
        $samplePrivate = $sampleEnabled && $hasSampleShipment
            ? $this->money($input['sample_private_charge'] ?? 0, 'sample_private_charge') : 0.0;
        $shipping = round($shipping + $remote + $private + $sampleShipping + $sampleRemote + $samplePrivate, 2);
        $bulkProductSubtotal = round($subtotal, 2);
        $subtotal = round($subtotal + $sampleProducts, 2);
        $customization = round($optionTotal + $imprintTotal + $sampleCustomization, 2);
        $grandTotal = round($subtotal + $customization + $shipping, 2);
        if (! $draft && ($grandTotal <= 0 || $grandTotal > 9999999999.99)) {
            throw ValidationException::withMessages(['lines' => 'Enter a positive quotation total before sending.']);
        }
        return [
            'quantity' => $qtyTotal, 'subtotal' => $subtotal, 'customization' => $customization,
            'shipping' => $shipping, 'shipping_method' => ucfirst($method),
            'pricing_lines' => $normalized,
            'sample' => ['enabled' => $sampleEnabled, 'shipping_method' => $sampleMethod, 'total' => round($sampleProducts + $sampleCustomization + $sampleShipping + $sampleRemote + $samplePrivate, 2)],
            'pricing_breakdown' => [
                'products' => $bulkProductSubtotal, 'options' => round($optionTotal, 2),
                'imprint' => round($imprintTotal, 2), 'sample_products' => round($sampleProducts, 2),
                'sample_customization' => round($sampleCustomization, 2),
                'shipping' => $shipping, 'remote_area_per_shipment' => $remote,
                'sample_remote_area_per_shipment' => $sampleRemote, 'grand_total' => $grandTotal,
            ],
        ];
    }

    /** @return array{0:array<string,string|array>,1:float,2:float} */
    private function resolveOptions(array $groups, array $selections, string $path, bool $required, string $productName): array
    {
        $validated = [];
        $unit = $fixed = 0.0;
        foreach ($groups as $group) {
            $chosen = $selections[$group['id']] ?? null;
            $matches = [];
            if ($group['type'] === 'checkbox') {
                if (! is_array($chosen)) $chosen = [];
                foreach ($chosen as $id) {
                    $value = collect($group['values'])->firstWhere('id', (string) $id);
                    if (! $value) throw ValidationException::withMessages([$path => 'An invalid product option was selected.']);
                    $matches[$value['id']] = $value;
                }
            } elseif ($chosen !== null && $chosen !== '') {
                if (! is_scalar($chosen)) throw ValidationException::withMessages([$path => 'An invalid product option was selected.']);
                $value = collect($group['values'])->firstWhere('id', (string) $chosen);
                if (! $value) throw ValidationException::withMessages([$path => 'An invalid product option was selected.']);
                $matches[$value['id']] = $value;
            }
            if ($group['fixed']) {
                $allowed = (array) $group['selected'];
                $selectedIds = array_map('strval', array_keys($matches));
                if ($selectedIds !== array_map('strval', $allowed)) {
                    throw ValidationException::withMessages([$path => 'A fixed product option cannot be changed.']);
                }
            }
            if ($required && $group['required'] && $matches === []) {
                throw ValidationException::withMessages([$path => 'Select '.$group['label'].' for '.$productName.'.']);
            }
            foreach ($matches as $value) {
                if ($value['charge_type'] === 'fixed_order') $fixed += (float) $value['price'];
                elseif ($value['charge_type'] !== 'included') $unit += (float) $value['price'];
            }
            // PHP coerces numeric-string array keys into integers; persist catalogue option IDs as strings.
            $validated[$group['id']] = $group['type'] === 'checkbox'
                ? array_map('strval', array_keys($matches))
                : (string) (array_key_first($matches) ?? '');
        }
        return [$validated, $unit, $fixed];
    }

    private function groups(Product $product, array $snapshot): array
    {
        $picked = collect($snapshot)->keyBy(fn ($choice) => mb_strtolower(trim((string) ($choice['label'] ?? ''))));
        $groups = [];
        foreach ($product->optionGroups->where('is_active', true) as $group) {
            if ($group->type === 'file' || ($group->display_mode ?: 'customer') === 'hidden') continue;
            if (! in_array($group->type, ['select', 'image', 'swatch', 'buttons', 'checkbox'], true)) continue;
            $values = $group->values->where('is_active', true)->map(fn ($value) => [
                'id' => (string) $value->id, 'label' => (string) $value->label,
                'price' => round(max(0, (float) $value->price_adjustment), 2),
                'charge_type' => in_array($value->charge_type, ['fixed_order', 'included'], true) ? $value->charge_type : 'per_unit',
                'default' => (bool) $value->is_default,
            ])->values()->all();
            if ($values === []) continue;
            $saved = $picked->get(mb_strtolower(trim((string) $group->name)));
            $chosenLabels = is_array($saved) ? ($saved['values'] ?? [$saved['value'] ?? '']) : [];
            // Snapshots store human-readable labels. Match them without case/spacing
            // sensitivity, including older quotes created before a label was normalized.
            $normalise = static fn ($text) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $text)));
            $selectedLabels = array_map($normalise, (array) $chosenLabels);
            $selected = collect($values)->filter(fn ($v) => in_array($normalise($v['label']), $selectedLabels, true))->pluck('id')->all();
            // A fixed value cannot be changed by an admin (same as the product configuration).
            if ($group->display_mode === 'fixed') {
                $fixed = $group->values->firstWhere('code', $group->fixed_value_code)
                    ?: $group->values->firstWhere('is_default', true);
                $selected = $fixed ? [(string) $fixed->id] : $selected;
            }
            $groups[] = [
                'id' => (string) $group->id, 'label' => (string) $group->name,
                'type' => (string) $group->type, 'required' => (bool) $group->is_required,
                'fixed' => $group->display_mode === 'fixed', 'values' => $values,
                'selected' => $group->type === 'checkbox' ? $selected : ($selected[0] ?? ''),
            ];
        }
        return $groups;
    }

    private function shippingRates(?Product $product, int $qty): array
    {
        $rates = ['regular' => 0.0, 'express' => 0.0];
        if (! $product) return $rates;
        $headers = (array) ($product->price_table_headers ?? []);
        $rows = (array) ($product->price_table_rows ?? []);
        $tiers = $product->priceTiers->toArray();
        foreach (['regular' => 'Regular shipping', 'express' => 'Express shipping'] as $key => $label) {
            $rates[$key] = max(0, (float) (PriceTableShipping::perUnitRate($headers, $rows, $tiers, $qty, $label) ?? 0));
        }
        if (! $product->shipping_methods_enabled) return $rates;
        foreach ($product->shippingMethods->where('is_active', true) as $method) {
            $name = mb_strtolower((string) ($method->shippingMethod?->name ?: $method->name ?: $method->code));
            $key = preg_match('/express|rush|priority|fast/', $name) ? 'express' : 'regular';
            $tableRate = PriceTableShipping::perUnitRate($headers, $rows, $tiers, $qty,
                (string) ($method->shippingMethod?->name ?? $method->name), (string) $method->code);
            $rates[$key] = round((float) ($tableRate ?? $method->base_price ?? $rates[$key])
                + max(0, (float) ($method->price_adjustment ?? 0)) * $qty, 2);
        }
        return $rates;
    }

    private function tableUnit(array $headers, array $rows, array $tiers, int $qty): ?float
    {
        $column = null;
        foreach ($headers as $index => $header) {
            if ($index > 0 && preg_match('/\b(unit|product)\b.*\bprice\b|\bprice\b.*\bunit\b/i', (string) $header)) {
                $column = (int) $index;
                break;
            }
        }
        if ($column === null || $rows === []) return null;
        $selected = 0;
        $bestMin = -1;
        foreach ($tiers as $index => $tier) {
            $minimum = (int) ($tier['min'] ?? $tier['minimum_quantity'] ?? 0);
            $maximum = $tier['max'] ?? $tier['maximum_quantity'] ?? null;
            if ($qty >= $minimum && ($maximum === null || $qty <= (int) $maximum) && $minimum >= $bestMin) {
                $selected = (int) $index;
                $bestMin = $minimum;
            }
        }
        if ($bestMin < 0) {
            foreach ($rows as $index => $row) {
                if (! is_array($row)) continue;
                if (preg_match('/\d[\d,]*/', (string) ($row[0] ?? ''), $match)
                    && $qty >= (int) str_replace(',', '', $match[0])) $selected = (int) $index;
            }
        }
        return PriceTableShipping::parseMoney(array_values((array) ($rows[$selected] ?? []))[$column] ?? null);
    }

    private function tierPrice(array $tiers, int $quantity, float $fallback): float
    {
        $price = $fallback;
        $bestMinimum = -1;
        foreach ($tiers as $tier) {
            if ($quantity >= $tier['min'] && ($tier['max'] === null || $quantity <= $tier['max'])
                && $tier['min'] >= $bestMinimum) {
                $bestMinimum = $tier['min'];
                $price = $tier['unit'];
            }
        }
        return round(max(0, $price), 2);
    }

    private function money(mixed $value, string $field): float
    {
        if (! is_scalar($value) || ! is_numeric($value) || (float) $value < 0 || (float) $value > 99999999) {
            throw ValidationException::withMessages([$field => 'Enter a valid non-negative USD amount.']);
        }
        return round((float) $value, 2);
    }

    private function integer(mixed $value, string $field, int $min, int $max): int
    {
        if (! is_scalar($value) || ! preg_match('/^\d+$/D', (string) $value) || (int) $value < $min || (int) $value > $max) {
            throw ValidationException::withMessages([$field => 'Enter a valid quantity.']);
        }
        return (int) $value;
    }
}
