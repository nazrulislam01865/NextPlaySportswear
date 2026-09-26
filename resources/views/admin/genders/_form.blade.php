@php
    $isEdit = $gender->exists;
    $isActive = old('is_active') !== null
        ? filter_var(old('is_active'), FILTER_VALIDATE_BOOLEAN)
        : (bool) ($gender->is_active ?? true);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($formMethod !== 'POST') @method($formMethod) @endif

    <x-admin.section-card title="Gender" description="Create a common master-data value that can be selected from the product add/edit page.">
        <div class="grid gap-5 lg:grid-cols-3">
            <label class="admin-label lg:col-span-2">
                Gender name
                <input type="text" name="name" value="{{ old('name', $gender->name) }}" class="admin-input" maxlength="120" placeholder="Men" required>
            </label>
            <label class="admin-label">
                Slug
                <input type="text" name="slug" value="{{ old('slug', $gender->slug) }}" class="admin-input" maxlength="120" placeholder="men">
                <span class="mt-2 block text-xs font-medium text-slate-500">Leave empty while creating to generate it from the name.</span>
            </label>
            <label class="admin-label">
                Sort order
                <input type="number" name="sort_order" value="{{ old('sort_order', $gender->sort_order ?? 0) }}" class="admin-input" min="0" max="999999">
                <span class="mt-2 block text-xs font-medium text-slate-500">Lower numbers appear first.</span>
            </label>
        </div>
    </x-admin.section-card>

    <x-admin.section-card title="Status" description="Only active gender values are offered when creating new products.">
        <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked($isActive) class="h-5 w-5 rounded border-slate-300 text-brand-red">
            Active
        </label>
    </x-admin.section-card>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.genders.index') }}" class="btn btn-white">Cancel</a>
        <button type="submit" class="btn btn-red">{{ $isEdit ? 'Update Gender' : 'Create Gender' }}</button>
    </div>
</form>
