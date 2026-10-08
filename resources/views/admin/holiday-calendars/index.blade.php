<x-layouts.admin title="Holiday Calendars" subtitle="Manage non-delivery dates by country. Active holiday dates automatically adjust affected delivery estimates.">
    <div class="space-y-6">
        <!-- Status & Flash Alerts -->
        @if(session('status'))
            <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-sm">
                <span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-200 text-xs font-black text-emerald-800">✓</span>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <!-- Header Row (Matching Screen 1 of Blueprint) -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-extrabold text-brand-ink">Holiday Calendars</h1>
                <p class="text-xs text-slate-500">Manage non-delivery dates by country. Active holiday dates automatically adjust affected delivery estimates.</p>
            </div>
            <a
                href="{{ route('admin.holiday-calendars.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition hover:opacity-95"
                style="background-color: #CF5D38 !important; color: #ffffff !important;"
            >
                <span class="text-sm leading-none">+</span>
                <span>Add Holiday Calendar</span>
            </a>
        </div>

        <!-- Filter Controls (Matching Screen 1 of Blueprint) -->
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.holiday-calendars.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Country Filter -->
                <div>
                    <select name="country" onchange="this.form.submit()" class="admin-input text-xs">
                        <option value="">All Countries</option>
                        @foreach($availableCountries as $ac)
                            <option value="{{ $ac->country_name }}" @selected($currentCountry === $ac->country_name)>
                                {{ $ac->country_name }} ({{ $ac->country_code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Year Filter -->
                <div>
                    <select name="year" onchange="this.form.submit()" class="admin-input text-xs">
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
                    <select name="status" onchange="this.form.submit()" class="admin-input text-xs">
                        <option value="">All Status</option>
                        <option value="active" @selected($currentStatus === 'active')>Active</option>
                        <option value="inactive" @selected($currentStatus === 'inactive')>Inactive</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="relative">
                    <input
                        type="text"
                        name="search"
                        value="{{ $currentSearch }}"
                        placeholder="Search..."
                        class="admin-input text-xs pr-8"
                    >
                    @if($currentSearch || $currentCountry || $currentYear || $currentStatus)
                        <a href="{{ route('admin.holiday-calendars.index') }}" class="absolute right-2.5 top-2.5 text-xs font-bold text-slate-400 hover:text-slate-600" title="Clear filters">✕</a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Holiday Calendars Table (Matching Screen 1 of Blueprint) -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card">
            <div class="admin-table-scroll" tabindex="0" aria-label="Holiday calendars table">
                <table class="admin-table min-w-[760px] text-xs">
                    <thead class="bg-slate-50 text-left text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3.5">Name</th>
                            <th class="px-5 py-3.5">Country</th>
                            <th class="px-5 py-3.5">Year</th>
                            <th class="px-5 py-3.5 text-center">Holidays</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse ($calendars as $calendar)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-5 py-4">
                                    <strong class="font-extrabold text-brand-ink text-xs block">{{ $calendar->name }}</strong>
                                    @if($calendar->description)
                                        <p class="mt-0.5 text-[11px] text-slate-400 max-w-sm truncate">{{ $calendar->description }}</p>
                                    @endif
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
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center justify-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-extrabold text-slate-700">
                                        {{ $calendar->dates_count }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    @if($calendar->is_active)
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-700 border border-emerald-200">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-rose-700 border border-rose-200">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a
                                            href="{{ route('admin.holiday-calendars.edit', $calendar) }}"
                                            class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                                        >
                                            Edit
                                        </a>

                                        <form method="POST" action="{{ route('admin.holiday-calendars.toggle-status', $calendar) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-600 hover:text-brand-ink hover:bg-slate-50 transition"
                                                title="{{ $calendar->is_active ? 'Deactivate Calendar' : 'Activate Calendar' }}"
                                            >
                                                {{ $calendar->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.holiday-calendars.destroy', $calendar) }}" onsubmit="return confirm('Delete this holiday calendar?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-lg border border-rose-200 bg-white px-2.5 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50 transition"
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
                                <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                    <span class="text-3xl block mb-2">📅</span>
                                    <p class="font-bold text-xs text-slate-600">No holiday calendars found.</p>
                                    <p class="text-[11px] text-slate-400 mt-1">Try adjusting your filters or click "+ Add Holiday Calendar" above.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $calendars->links('pagination.nextplay') }}
        </div>
    </div>
</x-layouts.admin>
