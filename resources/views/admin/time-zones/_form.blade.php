@php
    $isEdit = $timeZone->exists;
    $isActive = old('is_active') !== null
        ? filter_var(old('is_active'), FILTER_VALIDATE_BOOLEAN)
        : (bool) ($timeZone->is_active ?? true);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($formMethod !== 'POST') @method($formMethod) @endif

    <x-admin.section-card title="Time Zone" description="Add an IANA time zone that can be selected when scheduling a promotion.">
        <div class="grid gap-5 lg:grid-cols-3">
            <label class="admin-label">
                Display label
                <input type="text" name="label" value="{{ old('label', $timeZone->label) }}" class="admin-input" maxlength="160" placeholder="London" required>
            </label>
            <label class="admin-label lg:col-span-2">
                Time zone identifier
                <select name="identifier" class="admin-input" required>
                    <option value="">Choose a time zone</option>
                    @foreach($identifiers as $identifier)
                        <option value="{{ $identifier }}" @selected(old('identifier', $timeZone->identifier) === $identifier)>{{ $identifier }}</option>
                    @endforeach
                </select>
                <span class="np-field-help">Uses the standard IANA identifier so campaign dates stay unambiguous.</span>
            </label>
            <label class="admin-label">
                Sort order
                <input type="number" name="sort_order" value="{{ old('sort_order', $timeZone->sort_order ?? 0) }}" class="admin-input" min="0" max="1000000">
                <span class="np-field-help">Lower numbers appear first in the promotion form.</span>
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Status" description="Inactive values remain in Master Data but cannot be selected for a new promotion.">
        <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked($isActive) class="h-5 w-5 rounded border-slate-300 text-brand-red">
            Active
        </label>
    </x-admin.section-card>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.time-zones.index') }}" class="btn btn-white">Cancel</a>
        <button type="submit" class="btn btn-red">{{ $isEdit ? 'Update Time Zone' : 'Create Time Zone' }}</button>
    </div>
</form>
