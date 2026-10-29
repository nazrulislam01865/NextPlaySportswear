@php
    $canManage = auth('admin')->user()?->canAdmin('orders.manage') ?? false;
    $exampleRegion = match (strtoupper((string) $country->iso_code)) {
        'US' => 'California', 'CA' => 'Ontario', 'GB' => 'Greater London',
        'AU' => 'New South Wales', 'BD' => 'Dhaka', 'IN' => 'Maharashtra',
        'NZ' => 'Auckland', 'AE' => 'Dubai', default => $country->region_label,
    };
@endphp
@push('admin-page-styles')
    <link rel="stylesheet" href="{{ asset('css/country-region-master.css') }}?v=20261029c">
@endpush
<x-layouts.admin :title="$country->country_name.' — '.$country->region_label" eyebrow="Master Data / Countries" subtitle="Manage the region options available for this country's storefront addresses.">
    <div class="np-geo-page">
        <div class="mb-5 flex flex-wrap gap-3">
            <a href="{{ route('admin.country-calling-codes.index') }}" class="btn btn-white">Back to Countries</a>
            @if($canManage)
                <a href="{{ route('admin.country-calling-codes.edit', $country) }}" class="btn btn-white">Edit Country / Label</a>
            @endif
        </div>

        @if($canManage)
            <div class="np-geo-section">
                <x-admin.section-card :title="'Import '.$country->region_label.' for '.$country->country_name">
                    <form action="{{ route('admin.country-regions.import', $country) }}" method="POST" enctype="multipart/form-data" class="np-geo-import-form">
                        @csrf
                        <x-admin.geo-import-field input-id="regions-upload" name="regions_file" :label="'Choose '.$country->region_label.' file'" />
                        <div class="np-geo-import-actions">
                            <a href="{{ route('admin.country-regions.import-template', $country) }}" class="btn btn-white">Download Template</a>
                            <button type="submit" class="btn btn-red">Import Regions</button>
                        </div>
                    </form>
                    <p class="mt-2 text-xs text-slate-500">Existing regions and settings are kept. Only new regions or blank codes are filled.</p>
                    @error('regions_file')<p class="np-geo-inline-error" role="alert">{{ $message }}</p>@enderror
                </x-admin.section-card>
            </div>

            <div class="np-geo-section">
                <x-admin.section-card :title="'Add '.$country->region_label">
                    <form method="POST" action="{{ route('admin.country-regions.store', $country) }}" class="np-geo-field-grid">
                        @csrf
                        <label class="admin-label">Name
                            <input class="admin-input" name="name" maxlength="120" value="{{ old('name') }}" required placeholder="e.g. {{ $exampleRegion }}">
                        </label>
                        <label class="admin-label">Code (optional)
                            <input class="admin-input" name="code" maxlength="20" value="{{ old('code') }}" placeholder="e.g. GB-LND">
                        </label>
                        <label class="admin-label">Sort order
                            <input class="admin-input" type="number" name="sort_order" min="0" max="999999" value="{{ old('sort_order', 0) }}">
                        </label>
                        <button class="btn btn-red" type="submit">Add {{ $country->region_label }}</button>
                    </form>
                    @if($errors->has('name') || $errors->has('code') || $errors->has('sort_order'))
                        <p class="np-geo-inline-error" role="alert">{{ $errors->first('name') ?: ($errors->first('code') ?: $errors->first('sort_order')) }}</p>
                    @endif
                </x-admin.section-card>
            </div>
        @endif

        <div class="np-geo-table-wrap overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-card">
            <table class="admin-table w-full min-w-[680px] text-sm">
                <thead><tr><th class="px-5 py-3 text-left">Name</th><th>Code</th><th>Sort</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                    @forelse($regions as $region)
                        <tr>
                            @if($canManage)
                                <td colspan="5" class="p-3">
                                    <form id="region-{{ $region->id }}" action="{{ route('admin.country-regions.update', [$country, $region]) }}" method="POST" class="grid gap-2 md:grid-cols-[2fr_1fr_90px_110px_90px_auto] md:items-center">
                                        @csrf @method('PUT')
                                        <input class="admin-input" name="name" required maxlength="120" value="{{ $region->name }}" aria-label="Region name">
                                        <input class="admin-input" name="code" maxlength="20" value="{{ $region->code }}" placeholder="Code" aria-label="Region code">
                                        <input class="admin-input" name="sort_order" type="number" min="0" max="999999" value="{{ $region->sort_order }}" aria-label="Sort order">
                                        <select class="admin-input" name="is_active" aria-label="Status"><option value="1" @selected($region->is_active)>Active</option><option value="0" @selected(!$region->is_active)>Inactive</option></select>
                                        <button type="submit" class="btn btn-white">Save</button>
                                        <button type="submit" form="delete-region-{{ $region->id }}" class="btn btn-white text-red-700" onclick="return confirm('Delete region? Historical addresses will stay unchanged.')">Delete</button>
                                    </form>
                                    <form id="delete-region-{{ $region->id }}" method="POST" action="{{ route('admin.country-regions.destroy', [$country, $region]) }}">@csrf @method('DELETE')</form>
                                </td>
                            @else
                                <td class="px-5 py-3">{{ $region->name }}</td><td>{{ $region->code }}</td><td>{{ $region->sort_order }}</td><td>{{ $region->is_active ? 'Active' : 'Inactive' }}</td><td></td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="5" class="np-geo-empty">No {{ strtolower($country->region_label) }} entries yet. Import an Excel/CSV file or add one above.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $regions->links('pagination.nextplay') }}</div>
    </div>
</x-layouts.admin>
