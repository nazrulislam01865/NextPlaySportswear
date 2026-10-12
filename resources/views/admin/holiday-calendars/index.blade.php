<x-layouts.admin title="Holiday Calendars" subtitle="Manage non-delivery dates by country. Active holiday dates automatically adjust affected delivery estimates.">
    <div class="space-y-6">
        <!-- Status & Flash Alerts -->
        @if(session('status'))
            <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-sm">
                <span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-200 text-xs font-black text-emerald-800">✓</span>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <!-- Top Action Bar -->
        <div class="flex items-center justify-end">
            <a
                href="{{ route('admin.holiday-calendars.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-xs font-bold !text-white shadow-sm transition hover:opacity-95"
                style="background-color: #CF5D38 !important; color: #ffffff !important;"
            >
                <span class="text-sm leading-none font-black">+</span>
                <span>Add Holiday Calendar</span>
            </a>
        </div>

        <!-- Filter Controls (Matching Screen 1 of Blueprint) -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.holiday-calendars.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Country Filter -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Country</label>
                    <select name="country" onchange="this.form.submit()" class="admin-input text-xs w-full">
                        <option value="">All Countries</option>
                        @foreach($availableCountries as $ac)
                            <option value="{{ $ac->country_name }}" @selected($currentCountry === $ac->country_name)>
                                {{ \App\Support\CountryCatalog::flagEmoji($ac->country_code) }} {{ $ac->country_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Year Filter -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Year</label>
                    <select name="year" onchange="this.form.submit()" class="admin-input text-xs w-full">
                        <option value="">All Years</option>
                        @foreach($availableYears as $ay)
                            <option value="{{ $ay }}" @selected($currentYear == (string) $ay)>
                                {{ $ay }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Status</label>
                    <select name="status" onchange="this.form.submit()" class="admin-input text-xs w-full">
                        <option value="">All Status</option>
                        <option value="active" @selected($currentStatus === 'active')>Active</option>
                        <option value="inactive" @selected($currentStatus === 'inactive')>Inactive</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Search</label>
                    <div class="relative">
                        <input
                            type="text"
                            name="search"
                            value="{{ $currentSearch }}"
                            placeholder="Search calendars..."
                            class="admin-input text-xs pr-8 w-full"
                        >
                        @if($currentSearch || $currentCountry || $currentYear || $currentStatus)
                            <a href="{{ route('admin.holiday-calendars.index') }}" class="absolute right-2.5 top-2.5 text-xs font-bold text-slate-400 hover:text-slate-600" title="Clear filters">✕</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- Holiday Calendars Table (Matching Screen 1 of Blueprint) -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="admin-table-scroll" tabindex="0" aria-label="Holiday calendars table">
                <table class="admin-table min-w-[840px] text-xs">
                    <thead class="bg-slate-50/70 text-left text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3.5">Calendar Name</th>
                            <th class="px-5 py-3.5">Country</th>
                            <th class="px-5 py-3.5">Year</th>
                            <th class="px-5 py-3.5 text-center">Total Holidays</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5">Created</th>
                            <th class="px-5 py-3.5">Updated</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse ($calendars as $calendar)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-5 py-4">
                                    <a href="{{ route('admin.holiday-calendars.show', $calendar) }}" class="font-extrabold text-brand-ink text-xs hover:text-brand-red transition block">
                                        {{ $calendar->name }}
                                    </a>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base leading-none">{{ $calendar->flagEmoji() }}</span>
                                        <span class="font-bold text-slate-700">{{ $calendar->country_name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-bold text-slate-600">
                                    {{ $calendar->year }}
                                </td>
                                <td class="px-5 py-4 text-center font-bold text-slate-700">
                                    {{ $calendar->dates_count }}
                                </td>
                                <td class="px-5 py-4 text-center">
                                    @if($calendar->is_active)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                            <span>Active</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-600"></span>
                                            <span>Inactive</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-500 font-medium whitespace-nowrap">
                                    {{ $calendar->created_at?->format('d M') ?? '—' }}
                                </td>
                                <td class="px-5 py-4 text-slate-500 font-medium whitespace-nowrap">
                                    {{ $calendar->updated_at?->format('d M') ?? '—' }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a
                                            href="{{ route('admin.holiday-calendars.edit', $calendar) }}"
                                            class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                                        >
                                            Edit
                                        </a>

                                        <form method="POST" action="{{ route('admin.holiday-calendars.destroy', $calendar) }}" onsubmit="return confirm('Delete this holiday calendar?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-lg border border-rose-200 bg-white px-2 py-1.5 text-xs font-bold text-rose-500 hover:bg-rose-50 transition"
                                                title="Delete"
                                            >
                                                ✕
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                    <span class="text-3xl block mb-2">📅</span>
                                    <p class="font-bold text-xs text-slate-600">No holiday calendars found.</p>
                                    <p class="text-[11px] text-slate-400 mt-1">Try adjusting your filters or click "+ Add Holiday Calendar" above.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <p>Showing {{ $calendars->firstItem() ?? ($calendars->count() > 0 ? 1 : 0) }} to {{ $calendars->lastItem() ?? $calendars->count() }} of {{ $calendars->total() }} results</p>
                @if($calendars->hasPages())
                    <div>
                        {{ $calendars->links('pagination.nextplay-simple') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.admin>
