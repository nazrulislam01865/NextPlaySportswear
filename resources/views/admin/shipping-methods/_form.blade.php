@php
    $isEdit = $method->exists;
    $isActive = old('is_active') !== null ? filter_var(old('is_active'), FILTER_VALIDATE_BOOLEAN) : (bool) ($method->is_active ?? true);
    $isDefault = old('is_default') !== null ? filter_var(old('is_default'), FILTER_VALIDATE_BOOLEAN) : (bool) ($method->is_default ?? false);
    $isQuote = old('is_quote_based') !== null ? filter_var(old('is_quote_based'), FILTER_VALIDATE_BOOLEAN) : (bool) ($method->is_quote_based ?? false);
    $startsAfterArtwork = old('starts_after_artwork_approval') !== null ? filter_var(old('starts_after_artwork_approval'), FILTER_VALIDATE_BOOLEAN) : (bool) ($method->starts_after_artwork_approval ?? true);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($formMethod !== 'POST') @method($formMethod) @endif

    <x-admin.section-card title="Shipping Method" description="Create customer-facing shipping methods that checkout can calculate dynamically from cart quantity, subtotal, shipping address, and rural surcharge rules.">
        <div class="grid gap-5 lg:grid-cols-4">
            <label class="admin-label lg:col-span-2">
                Method name
                <input type="text" name="name" value="{{ old('name', $method->name) }}" class="admin-input" maxlength="160" placeholder="Standard Shipping" required>
            </label>
            <label class="admin-label">
                Code
                <input type="text" name="code" value="{{ old('code', $method->code) }}" class="admin-input" maxlength="160" placeholder="standard-shipping">
                <span class="mt-2 block text-xs font-medium text-slate-500">Leave empty while creating to auto-generate from the name.</span>
            </label>
            <label class="admin-label">
                Sort order
                <input type="number" name="sort_order" value="{{ old('sort_order', $method->sort_order ?? 0) }}" class="admin-input" min="0" max="999999">
            </label>
            <label class="admin-label lg:col-span-4">
                Description
                <textarea name="description" class="admin-textarea min-h-[110px]" maxlength="2000" placeholder="Shown to customers during checkout.">{{ old('description', $method->description) }}</textarea>
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Pricing Rules" description="Shipping price = base price + per item price for additional items. Free shipping can be enabled by minimum subtotal.">
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <label class="admin-label">
                Base price
                <input type="number" name="base_price" value="{{ old('base_price', $method->base_price ?? 0) }}" class="admin-input" min="0" max="999999.99" step="0.01" required>
            </label>
            <label class="admin-label">
                Per additional item
                <input type="number" name="per_item_price" value="{{ old('per_item_price', $method->per_item_price ?? 0) }}" class="admin-input" min="0" max="999999.99" step="0.01">
            </label>
            <label class="admin-label">
                Free shipping minimum
                <input type="number" name="free_shipping_minimum" value="{{ old('free_shipping_minimum', $method->free_shipping_minimum) }}" class="admin-input" min="0" max="999999.99" step="0.01" placeholder="Optional">
            </label>
            <label class="admin-label">
                Quote based?
                <span class="mt-2 flex min-h-[46px] items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-black text-slate-700">
                    <input type="hidden" name="is_quote_based" value="0">
                    <input type="checkbox" name="is_quote_based" value="1" @checked($isQuote) class="h-5 w-5 rounded border-slate-300 text-brand-red">
                    Quote / manual review
                </span>
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Availability Rules" description="Optional restrictions help you show shipping methods only when they make sense for the order.">
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <label class="admin-label">
                Minimum quantity
                <input type="number" name="minimum_quantity" value="{{ old('minimum_quantity', $method->minimum_quantity) }}" class="admin-input" min="1" max="1000000" placeholder="Optional">
            </label>
            <label class="admin-label">
                Maximum quantity
                <input type="number" name="maximum_quantity" value="{{ old('maximum_quantity', $method->maximum_quantity) }}" class="admin-input" min="1" max="1000000" placeholder="Optional">
            </label>
            <label class="admin-label">
                Minimum subtotal
                <input type="number" name="minimum_subtotal" value="{{ old('minimum_subtotal', $method->minimum_subtotal) }}" class="admin-input" min="0" max="999999999.99" step="0.01" placeholder="Optional">
            </label>
            <label class="admin-label">
                Maximum subtotal
                <input type="number" name="maximum_subtotal" value="{{ old('maximum_subtotal', $method->maximum_subtotal) }}" class="admin-input" min="0" max="999999999.99" step="0.01" placeholder="Optional">
            </label>
            <label class="admin-label">
                Country
                <input type="text" name="country" value="{{ old('country', $method->country) }}" class="admin-input" maxlength="120" placeholder="Optional">
            </label>
            <label class="admin-label">
                State / Province
                <input type="text" name="state" value="{{ old('state', $method->state) }}" class="admin-input" maxlength="120" placeholder="Optional">
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Delivery Estimate" description="For custom sportswear, the delivery clock should usually start after artwork/proof approval, not immediately after order placement.">
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <label class="admin-label">
                Transit minimum days
                <input type="number" name="minimum_days" value="{{ old('minimum_days', $method->minimum_days ?? 1) }}" class="admin-input" min="0" max="3650" required>
            </label>
            <label class="admin-label">
                Transit maximum days
                <input type="number" name="maximum_days" value="{{ old('maximum_days', $method->maximum_days ?? 7) }}" class="admin-input" min="0" max="3650" required>
            </label>
            <label class="flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-black text-blue-800 xl:col-span-2">
                <input type="hidden" name="starts_after_artwork_approval" value="0">
                <input type="checkbox" name="starts_after_artwork_approval" value="1" @checked($startsAfterArtwork) class="h-5 w-5 rounded border-blue-300 text-blue-600">
                Start delivery estimate after artwork confirmation
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Status" description="Only active shipping methods are shown to customers.">
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-800">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked($isActive) class="h-5 w-5 rounded border-emerald-300 text-emerald-600">
                Active
            </label>
            <label class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-black text-amber-800">
                <input type="hidden" name="is_default" value="0">
                <input type="checkbox" name="is_default" value="1" @checked($isDefault) class="h-5 w-5 rounded border-amber-300 text-amber-600">
                Default customer choice
            </label>
        </div>
    </x-admin.section-card>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.shipping-methods.index') }}" class="btn btn-white">Cancel</a>
        <button type="submit" class="btn btn-red">{{ $isEdit ? 'Update Shipping Method' : 'Create Shipping Method' }}</button>
    </div>
</form>
