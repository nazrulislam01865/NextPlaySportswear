@php
    $isEdit = $surcharge->exists;
    $isActive = old('is_active') !== null ? filter_var(old('is_active'), FILTER_VALIDATE_BOOLEAN) : (bool) ($surcharge->is_active ?? true);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <x-admin.section-card title="Surcharge Rule" description="Use exact ZIP codes, wildcard prefixes such as 995*, comma-separated values, or numeric ranges such as 99500-99999.">
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <label class="admin-label sm:col-span-2">
                Rule name
                <input type="text" name="name" value="{{ old('name', $surcharge->name) }}" class="admin-input" maxlength="150" placeholder="Remote Alaska surcharge" required>
            </label>
            <label class="admin-label">
                Country
                <input type="text" name="country" value="{{ old('country', $surcharge->country ?: 'United States') }}" class="admin-input" maxlength="120" required>
            </label>
            <label class="admin-label">
                State / Province
                <input type="text" name="state" value="{{ old('state', $surcharge->state) }}" class="admin-input" maxlength="120" placeholder="Optional">
            </label>
            <label class="admin-label sm:col-span-2 xl:col-span-3">
                ZIP / postal code patterns
                <textarea name="postal_code_patterns" class="admin-textarea min-h-[150px]" maxlength="4000" placeholder="995*&#10;99600-99999&#10;12345, 12346" required>{{ old('postal_code_patterns', $surcharge->postal_code_patterns) }}</textarea>
                <span class="mt-2 block text-xs font-medium leading-5 text-slate-500">Each line or comma can contain one exact ZIP, wildcard prefix, or numeric range.</span>
            </label>
            <div class="grid gap-5 sm:grid-cols-2 xl:col-span-1 xl:grid-cols-1">
                <label class="admin-label">
                    Surcharge amount
                    <input type="number" name="amount" value="{{ old('amount', $surcharge->amount) }}" class="admin-input" min="0.01" max="999999.99" step="0.01" required>
                </label>
                <label class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-800">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked($isActive) class="h-5 w-5 rounded border-emerald-300 text-emerald-600">
                    Active
                </label>
            </div>
        </div>
    </x-admin.section-card>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.rural-area-surcharges.index') }}" class="btn btn-white">Cancel</a>
        <button type="submit" class="btn btn-red">{{ $isEdit ? 'Update Surcharge' : 'Create Surcharge' }}</button>
    </div>
</form>
