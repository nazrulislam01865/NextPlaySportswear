@php
    $isEdit = $method->exists;
    $isActive = old('is_active') !== null ? filter_var(old('is_active'), FILTER_VALIDATE_BOOLEAN) : (bool) ($method->is_active ?? true);
    $isDefault = old('is_default') !== null ? filter_var(old('is_default'), FILTER_VALIDATE_BOOLEAN) : (bool) ($method->is_default ?? false);
    $startsAfterArtwork = old('starts_after_artwork_approval') !== null ? filter_var(old('starts_after_artwork_approval'), FILTER_VALIDATE_BOOLEAN) : (bool) ($method->starts_after_artwork_approval ?? true);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($formMethod !== 'POST') @method($formMethod) @endif

    <x-admin.section-card title="Shipping Method" description="Create a clean reusable shipping option that can be selected in product setup and checkout.">
        <div class="grid gap-5 lg:grid-cols-3">
            <label class="admin-label lg:col-span-2">
                Method name
                <input type="text" name="name" value="{{ old('name', $method->name) }}" class="admin-input" maxlength="160" placeholder="Standard Shipping" required>
            </label>
            <label class="admin-label">
                Code
                <input type="text" name="code" value="{{ old('code', $method->code) }}" class="admin-input" maxlength="160" placeholder="standard-shipping">
                <span class="mt-2 block text-xs font-medium text-slate-500">Leave empty while creating to auto-generate from the name.</span>
            </label>
            <label class="admin-label lg:col-span-3">
                Description
                <textarea name="description" class="admin-textarea min-h-[110px]" maxlength="2000" placeholder="Shown to customers during checkout and product setup.">{{ old('description', $method->description) }}</textarea>
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Pricing Source" description="Shipping prices are not set in master data anymore.">
        <input type="hidden" name="charge_amount" value="0">
        <input type="hidden" name="charge_application" value="included">
        <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm font-semibold leading-6 text-blue-800">
            This master record controls only the shipping method name and transit days. The customer price is read from each product price table, using columns such as <strong>Est. Standard Shipping</strong>, <strong>Normal Shipping</strong>, <strong>Est. Urgent Shipping</strong>, <strong>Express Shipping</strong>, or <strong>Remote Area Surcharge</strong>.
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
            <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 xl:col-span-2">
                <input type="hidden" name="starts_after_artwork_approval" value="0">
                <input type="checkbox" name="starts_after_artwork_approval" value="1" @checked($startsAfterArtwork) class="h-5 w-5 rounded border-slate-300 text-brand-red">
                Start delivery estimate after artwork confirmation
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Status" description="Only active shipping methods are shown to customers. Choose one default method for easier checkout.">
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked($isActive) class="h-5 w-5 rounded border-slate-300 text-brand-red">
                Active
            </label>
            <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                <input type="hidden" name="is_default" value="0">
                <input type="checkbox" name="is_default" value="1" @checked($isDefault) class="h-5 w-5 rounded border-slate-300 text-brand-red">
                Default customer choice
            </label>
        </div>
    </x-admin.section-card>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.shipping-methods.index') }}" class="btn btn-white">Cancel</a>
        <button type="submit" class="btn btn-red">{{ $isEdit ? 'Update Shipping Method' : 'Create Shipping Method' }}</button>
    </div>
</form>
