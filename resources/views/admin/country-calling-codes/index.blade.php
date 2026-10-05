@php
    $canManageBulkQuoteMasterData = auth('admin')->user()?->canAdmin('orders.manage') ?? false;
@endphp
<x-layouts.admin title="Country Calling Code Master Data" subtitle="Manage the calling codes available on the Bulk Quote form.">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="max-w-3xl text-sm font-medium leading-6 text-slate-500">Active values appear in the Phone / WhatsApp country-code dropdown. The code is combined with the entered phone number when the quote is saved.</p>
        @if($canManageBulkQuoteMasterData)
            <a href="{{ route('admin.country-calling-codes.create') }}" class="btn btn-red">+ Add Country Code</a>
        @endif
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="admin-table-scroll" tabindex="0" aria-label="Country calling code master data table">
            <table class="admin-table min-w-[760px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Country</th>
                        <th class="px-5 py-4">ISO</th>
                        <th class="px-5 py-4">Calling Code</th>
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
                            <td class="px-5 py-4">
                                <span class="admin-status-pill px-2.5 py-1 text-xs font-semibold {{ $code->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $code->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="px-5 py-4 font-medium text-slate-700">{{ $code->sort_order }}</td>
                            <td class="px-5 py-4">
                                @if($canManageBulkQuoteMasterData)
                                    <div class="admin-row-actions">
                                        <a class="admin-row-action border-slate-200" href="{{ route('admin.country-calling-codes.edit', $code) }}">Edit</a>
                                        <form method="POST" action="{{ route('admin.country-calling-codes.destroy', $code) }}" onsubmit="return confirm('Delete this country calling code? Existing saved quotes will not be changed.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">Delete</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-sm text-slate-400">View only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-14 text-center text-slate-500">No country calling codes have been added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $countryCallingCodes->links('pagination.nextplay') }}</div>
</x-layouts.admin>
