@php
    $isEdit = $budgetRange->exists;
    $isActive = old('is_active') !== null
        ? filter_var(old('is_active'), FILTER_VALIDATE_BOOLEAN)
        : (bool) ($budgetRange->is_active ?? true);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($formMethod !== 'POST') @method($formMethod) @endif

    <x-admin.section-card title="Budget Range" description="Create a reusable range shown in the Bulk Quote Budget Range dropdown.">
        <div class="grid gap-5 lg:grid-cols-3">
            <label class="admin-label lg:col-span-2">
                Display label
                <input type="text" name="label" value="{{ old('label', $budgetRange->label) }}" class="admin-input" maxlength="120" placeholder="$500–$1,500" required>
            </label>
            <label class="admin-label">
                Internal value
                <input type="text" name="value" value="{{ old('value', $budgetRange->value) }}" class="admin-input" maxlength="80" placeholder="500-1500">
                <span class="np-field-help">Leave empty when creating to generate it from the label.</span>
            </label>
            <label class="admin-label">
                Sort order
                <input type="number" name="sort_order" value="{{ old('sort_order', $budgetRange->sort_order ?? 0) }}" class="admin-input" min="0" max="999999">
                <span class="np-field-help">Lower numbers appear first in the dropdown.</span>
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Status" description="Inactive values remain in Master Data but are hidden from new Bulk Quote requests.">
        <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked($isActive) class="h-5 w-5 rounded border-slate-300 text-brand-red">
            Active
        </label>
    </x-admin.section-card>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.bulk-quote-budget-ranges.index') }}" class="btn btn-white">Cancel</a>
        <button type="submit" class="btn btn-red">{{ $isEdit ? 'Update Budget Range' : 'Create Budget Range' }}</button>
    </div>
</form>
