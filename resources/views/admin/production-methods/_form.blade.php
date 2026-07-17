@php
    $isEdit = $method->exists;
    $isActive = old('is_active') !== null ? filter_var(old('is_active'), FILTER_VALIDATE_BOOLEAN) : (bool) ($method->is_active ?? true);
    $isDefault = old('is_default') !== null ? filter_var(old('is_default'), FILTER_VALIDATE_BOOLEAN) : (bool) ($method->is_default ?? false);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($formMethod !== 'POST') @method($formMethod) @endif

    <x-admin.section-card title="Production Method" description="Create a reusable production option that can be selected in product setup.">
        <div class="grid gap-5 lg:grid-cols-3">
            <label class="admin-label lg:col-span-2">
                Method name
                <input type="text" name="name" value="{{ old('name', $method->name) }}" class="admin-input" maxlength="160" placeholder="Standard Production" required>
            </label>
            <label class="admin-label">
                Code
                <input type="text" name="code" value="{{ old('code', $method->code) }}" class="admin-input" maxlength="160" placeholder="standard-production">
                <span class="mt-2 block text-xs font-medium text-slate-500">Leave empty while creating to auto-generate from the name.</span>
            </label>
            <label class="admin-label lg:col-span-3">
                Description
                <textarea name="description" class="admin-textarea min-h-[110px]" maxlength="2000" placeholder="Shown to customers on the product page.">{{ old('description', $method->description) }}</textarea>
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Production Timeline" description="This is the production working-day range shown to customers before shipping.">
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <label class="admin-label">
                Minimum working days
                <input type="number" name="minimum_days" value="{{ old('minimum_days', $method->minimum_days ?? 7) }}" class="admin-input" min="0" max="3650" required>
            </label>
            <label class="admin-label">
                Maximum working days
                <input type="number" name="maximum_days" value="{{ old('maximum_days', $method->maximum_days ?? 10) }}" class="admin-input" min="0" max="3650" required>
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Status" description="Only active production methods are shown in product setup and on the storefront.">
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
        <a href="{{ route('admin.production-methods.index') }}" class="btn btn-white">Cancel</a>
        <button type="submit" class="btn btn-red">{{ $isEdit ? 'Update Production Method' : 'Create Production Method' }}</button>
    </div>
</form>
