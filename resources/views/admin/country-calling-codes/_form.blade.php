@php
    $isEdit = $countryCallingCode->exists;
    $isActive = old('is_active') !== null
        ? filter_var(old('is_active'), FILTER_VALIDATE_BOOLEAN)
        : (bool) ($countryCallingCode->is_active ?? true);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($formMethod !== 'POST') @method($formMethod) @endif

    <x-admin.section-card title="Country Calling Code" description="This value is used by the country-code dropdown beside Phone / WhatsApp on the Bulk Quote form.">
        <div class="grid gap-5 lg:grid-cols-4">
            <label class="admin-label lg:col-span-2">
                Country name
                <input type="text" name="country_name" value="{{ old('country_name', $countryCallingCode->country_name) }}" class="admin-input" maxlength="120" placeholder="United States" required>
            </label>
            <label class="admin-label">
                ISO code
                <input type="text" name="iso_code" value="{{ old('iso_code', $countryCallingCode->iso_code) }}" class="admin-input uppercase" maxlength="2" placeholder="US" required>
                <span class="np-field-help">Two letters. Used to display the country flag.</span>
            </label>
            <label class="admin-label">
                Calling code
                <input type="text" name="dial_code" value="{{ old('dial_code', $countryCallingCode->dial_code) }}" class="admin-input" maxlength="12" placeholder="+1" required>
            </label>
            <label class="admin-label">
                Sort order
                <input type="number" name="sort_order" value="{{ old('sort_order', $countryCallingCode->sort_order ?? 0) }}" class="admin-input" min="0" max="999999">
                <span class="np-field-help">Lower numbers appear first in the dropdown.</span>
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Status" description="Inactive values remain in Master Data but cannot be selected on a new Bulk Quote.">
        <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked($isActive) class="h-5 w-5 rounded border-slate-300 text-brand-red">
            Active
        </label>
    </x-admin.section-card>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.country-calling-codes.index') }}" class="btn btn-white">Cancel</a>
        <button type="submit" class="btn btn-red">{{ $isEdit ? 'Update Country Code' : 'Create Country Code' }}</button>
    </div>
</form>
