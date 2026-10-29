@php
    $canManageBulkQuoteMasterData = auth('admin')->user()?->canAdmin('orders.manage') ?? false;
@endphp
@push('admin-page-styles')
    <link rel="stylesheet" href="{{ asset('css/country-region-master.css') }}?v=20261029c">
@endpush
<x-layouts.admin title="Countries & Regions Master Data" subtitle="Centralised country, phone code and state/province master data for all storefront address forms.">
    <div class="np-geo-page">
        @if($canManageBulkQuoteMasterData)
            <div class="np-geo-toolbar">
                <a href="{{ route('admin.country-calling-codes.create') }}" class="btn btn-red">+ Add Country</a>
            </div>

            <div class="np-geo-section">
                <x-admin.section-card title="Import Countries & Regions">
                    <form action="{{ route('admin.country-geography.import') }}" method="POST" enctype="multipart/form-data" class="np-geo-import-form">
                        @csrf
                        <x-admin.geo-import-field input-id="country-geography-upload" name="file" label="Choose a country and region file" />
                        <div class="np-geo-import-actions">
                            <a href="{{ route('admin.country-geography.template') }}" class="btn btn-white">Download Template</a>
                            <button type="submit" class="btn btn-red">Import File</button>
                        </div>
                    </form>
                    <p class="mt-2 text-xs text-slate-500">Safe import: existing settings stay unchanged; only missing entries or blank region codes are filled.</p>
                    @error('file')<p class="np-geo-inline-error" role="alert">{{ $message }}</p>@enderror
                </x-admin.section-card>
            </div>
        @endif

        <div class="np-geo-table-shell overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="admin-table-scroll" tabindex="0" aria-label="Countries master data table">
                <table class="admin-table min-w-[760px] text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4">Country</th>
                            <th class="px-5 py-4">ISO</th>
                            <th class="px-5 py-4">Calling Code</th>
                            <th class="px-5 py-4">Region Label</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4">Sort</th>
                            <th class="px-5 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($countryCallingCodes as $code)
                            <tr>
                                <td class="px-5 py-4">
                                    <strong class="block font-semibold text-brand-ink">{{ $code->flagEmoji() }} {{ $code->country_name }}</strong>
                                </td>
                                <td class="px-5 py-4 font-medium text-slate-700">{{ $code->iso_code }}</td>
                                <td class="px-5 py-4 font-black text-brand-ink">{{ $code->dial_code }}</td>
                                <td class="px-5 py-4 text-slate-700">{{ $code->region_label }}</td>
                                <td class="px-5 py-4">
                                    <span class="admin-status-pill px-2.5 py-1 text-xs font-semibold {{ $code->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $code->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="px-5 py-4 font-medium text-slate-700">{{ $code->sort_order }}</td>
                                <td class="px-5 py-4">
                                    @if($canManageBulkQuoteMasterData)
                                        <div class="admin-row-actions">
                                            <a class="admin-row-action border-slate-200" href="{{ route('admin.country-regions.index', $code) }}">Regions / Import</a>
                                            <a class="admin-row-action border-slate-200" href="{{ route('admin.country-calling-codes.edit', $code) }}">Edit</a>
                                            <details class="relative np-geo-more-menu">
                                                <summary class="admin-row-action border-slate-200 cursor-pointer" aria-label="More country actions">More</summary>
                                                <div class="absolute right-0 z-30 mt-1 min-w-[160px] rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                                                    <a class="mb-1 block rounded-lg px-3 py-2 text-sm font-semibold text-brand-ink hover:bg-slate-50" href="{{ route('admin.holiday-calendars.index', ['country' => $code->country_name]) }}">Calendars</a>
                                                    <form method="POST" action="{{ route('admin.country-calling-codes.destroy', $code) }}" onsubmit="return confirm('Delete this country and its configured regions? Existing saved quotes will not be changed.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-red-700 hover:bg-red-50">Delete Country</button>
                                                    </form>
                                                </div>
                                            </details>
                                        </div>
                                    @else
                                        <span class="text-sm text-slate-400">View only</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-14 text-center text-slate-500">No countries have been added yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-5">{{ $countryCallingCodes->links('pagination.nextplay') }}</div>
    </div>
</x-layouts.admin>
