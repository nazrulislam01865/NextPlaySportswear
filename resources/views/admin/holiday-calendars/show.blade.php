<x-layouts.admin :title="$calendar->name" subtitle="View calendar configuration and all non-delivery dates assigned to this country.">
    <div class="space-y-6">
        <!-- Top Action Bar -->
        <div class="flex items-center justify-end">
            <a
                href="{{ route('admin.holiday-calendars.edit', $calendar) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-xs font-bold !text-white shadow-sm transition hover:opacity-95"
                style="background-color: var(--np-color-secondary) !important; color: #ffffff !important;"
            >
                <span>Edit Calendar</span>
            </a>
        </div>

        @if(session('status'))
            <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-sm">
                <span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-200 text-xs font-black text-emerald-800">✓</span>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <!-- Card 1: Calendar Summary (Matching Screen 3 of Blueprint) -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-extrabold text-brand-ink mb-4">Calendar Summary</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <span class="block text-xs text-slate-400 font-medium">Country</span>
                    <strong class="mt-1 block text-sm font-extrabold text-brand-ink">{{ $calendar->country_name }}</strong>
                </div>
                <div>
                    <span class="block text-xs text-slate-400 font-medium">Year</span>
                    <strong class="mt-1 block text-sm font-extrabold text-brand-ink">{{ $calendar->year }}</strong>
                </div>
                <div>
                    <span class="block text-xs text-slate-400 font-medium">Total holidays</span>
                    <strong class="mt-1 block text-sm font-extrabold text-brand-ink">{{ $dates->count() }}</strong>
                </div>
                <div>
                    <span class="block text-xs text-slate-400 font-medium">Status</span>
                    <div class="mt-1">
                        @if($calendar->is_active)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                <span>Active</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-600"></span>
                                <span>Inactive</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Holiday Dates (Matching Screen 3 of Blueprint) -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="p-6 border-b border-slate-100">
                <h2 class="text-sm font-extrabold text-brand-ink">Holiday Dates</h2>
            </div>
            <div class="admin-table-scroll" tabindex="0" aria-label="Holiday dates table">
                <table class="admin-table min-w-[640px] text-xs">
                    <thead class="bg-slate-50/70 text-left text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-6 py-3.5">Date</th>
                            <th class="px-6 py-3.5">Holiday Name</th>
                            <th class="px-6 py-3.5">Day</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse($dates as $dateItem)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-6 py-4 font-mono text-xs text-slate-600">
                                    {{ $dateItem->date->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 font-extrabold text-brand-ink">
                                    {{ $dateItem->name }}
                                </td>
                                <td class="px-6 py-4 text-slate-500 font-medium">
                                    {{ $dateItem->date->format('l') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-xs text-slate-400">
                                    No holiday dates configured for this calendar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.admin>
