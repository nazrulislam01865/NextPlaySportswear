<x-layouts.admin title="Create Holiday Calendar" subtitle="Add a new country holiday calendar to automatically adjust delivery estimates.">
    <div class="space-y-6" x-data="{
        country: '{{ old('country', 'GB|United Kingdom') }}',
        get countryName() {
            return this.country.includes('|') ? this.country.split('|')[1] : this.country;
        },
        dates: {{ Js::from(old('dates', [])) }},
        addDate() {
            this.dates.push({
                date: '',
                name: '',
                notes: ''
            });
        },
        removeDate(index) {
            this.dates.splice(index, 1);
        }
    }">
        <!-- Top Navigation / Breadcrumb -->
        <div class="flex items-center justify-between">
            <a
                href="{{ route('admin.holiday-calendars.index') }}"
                class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-brand-ink transition"
            >
                <span class="text-sm">←</span>
                <span>Back to Holiday Calendars</span>
            </a>
        </div>

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800 shadow-sm">
                <div class="flex items-center gap-2 font-bold mb-1">
                    <span class="grid h-5 w-5 place-items-center rounded-full bg-rose-200 text-xs font-black text-rose-800">!</span>
                    <span>Please correct the errors below:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.holiday-calendars.store') }}" class="space-y-6">
            @csrf

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Left: Calendar Details (2 cols) -->
                <div class="space-y-6 lg:col-span-2">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card space-y-5">
                        <div class="border-b border-slate-100 pb-3">
                            <h2 class="text-sm font-extrabold text-brand-ink">Calendar Details</h2>
                            <p class="text-xs text-slate-500">Configure country and active status for this calendar.</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <!-- Name -->
                            <div class="sm:col-span-2">
                                <label class="admin-label text-xs font-bold text-slate-700" for="name">
                                    Calendar Name <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    value="{{ old('name', $calendar->name) }}"
                                    placeholder="e.g. UK Bank Holidays 2026 or Carrier Closures"
                                    class="admin-input text-xs @error('name') border-rose-400 @enderror"
                                    required
                                >
                                @error('name')
                                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Country -->
                            <div>
                                <label class="admin-label text-xs font-bold text-slate-700" for="country">
                                    Country <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    id="country"
                                    name="country"
                                    x-model="country"
                                    class="admin-input text-xs @error('country_code') border-rose-400 @enderror"
                                    required
                                >
                                    @foreach($countries as $c)
                                        <option value="{{ $c['code'] }}|{{ $c['name'] }}">
                                            {{ $c['flag'] }} {{ $c['name'] }} ({{ $c['code'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Year -->
                            <div>
                                <label class="admin-label text-xs font-bold text-slate-700" for="year">
                                    Year <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="year"
                                    name="year"
                                    value="{{ old('year', $calendar->year ?? date('Y')) }}"
                                    min="2020"
                                    max="2050"
                                    class="admin-input text-xs @error('year') border-rose-400 @enderror"
                                    required
                                >
                                @error('year')
                                    <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div class="sm:col-span-2">
                                <label class="admin-label text-xs font-bold text-slate-700" for="description">
                                    Description / Notes <span class="text-slate-400 font-normal">(Optional)</span>
                                </label>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows="2"
                                    placeholder="Brief notes about these non-operational dates..."
                                    class="admin-input text-xs"
                                >{{ old('description', $calendar->description) }}</textarea>
                            </div>

                            <!-- Active Checkbox -->
                            <div class="sm:col-span-2 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                <label class="relative flex items-start gap-3 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        name="is_active"
                                        value="1"
                                        class="h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red mt-0.5"
                                        @checked(old('is_active', $calendar->is_active ?? true))
                                    >
                                    <div>
                                        <span class="block text-xs font-extrabold text-brand-ink">Active Calendar</span>
                                        <span class="block text-[11px] text-slate-500">
                                            When enabled, non-working dates in this calendar automatically extend delivery date calculations and recalculate open customer orders.
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Delivery Impact Notice (1 col - Matching Screen 2 of Blueprint) -->
                <div class="space-y-6">
                    <div class="rounded-2xl border border-amber-300 bg-amber-50/90 p-5 shadow-sm space-y-3">
                        <div class="flex items-center gap-2.5 text-amber-900">
                            <span class="text-lg">⚠️</span>
                            <h3 class="text-xs font-extrabold uppercase tracking-wider">Delivery Impact Notice</h3>
                        </div>
                        <p class="text-xs text-amber-900 leading-relaxed font-medium">
                            Active holiday dates automatically extend delivery windows by skipping non-working days for all standard and express shipping in <strong class="font-extrabold" x-text="countryName">selected country</strong>.
                        </p>
                        <p class="text-[11px] text-amber-800 leading-relaxed border-t border-amber-200/80 pt-2.5">
                            Any existing unfulfilled customer orders with delivery addresses in this country will be automatically recalculated upon saving.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                        <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-wider">Quick Guidelines</h3>
                        <ul class="text-[11px] text-slate-600 space-y-2 list-disc list-inside">
                            <li>Add official national bank holidays & carrier closed dates.</li>
                            <li>Weekends (Saturday & Sunday) are automatically excluded as non-working days.</li>
                            <li>Dates in the past are safely preserved for historical accuracy.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Bottom: Holiday Dates Dynamic Table (Matching Screen 2 of Blueprint) -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-card overflow-hidden">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 p-5">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-extrabold text-brand-ink">Holiday Dates</h2>
                            <span class="inline-flex items-center justify-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-extrabold text-slate-700" x-text="dates.length + ' dates'"></span>
                        </div>
                        <p class="text-xs text-slate-500">Individual days on which carriers will not dispatch or deliver.</p>
                    </div>
                    <button
                        type="button"
                        @click="addDate()"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-sm transition"
                    >
                        <span class="text-emerald-600 font-extrabold text-sm leading-none">+</span>
                        <span>Add Holiday</span>
                    </button>
                </div>

                <div class="admin-table-scroll" tabindex="0" aria-label="Holiday dates table">
                    <table class="admin-table min-w-[640px] text-xs">
                        <thead class="bg-slate-50 text-left text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3.5 w-48">Date <span class="text-rose-500">*</span></th>
                                <th class="px-5 py-3.5">Holiday Name <span class="text-rose-500">*</span></th>
                                <th class="px-5 py-3.5">Carrier / Detail Notes</th>
                                <th class="px-5 py-3.5 text-right w-24">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            <template x-for="(item, index) in dates" :key="index">
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="px-5 py-3">
                                        <input
                                            type="date"
                                            :name="'dates[' + index + '][date]'"
                                            x-model="item.date"
                                            class="admin-input text-xs font-semibold py-1.5"
                                            required
                                        >
                                    </td>
                                    <td class="px-5 py-3">
                                        <input
                                            type="text"
                                            :name="'dates[' + index + '][name]'"
                                            x-model="item.name"
                                            placeholder="e.g. Christmas Day"
                                            class="admin-input text-xs py-1.5"
                                            required
                                        >
                                    </td>
                                    <td class="px-5 py-3">
                                        <input
                                            type="text"
                                            :name="'dates[' + index + '][notes]'"
                                            x-model="item.notes"
                                            placeholder="Optional note"
                                            class="admin-input text-xs py-1.5"
                                        >
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <button
                                            type="button"
                                            @click="removeDate(index)"
                                            class="inline-flex items-center rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 transition"
                                            title="Remove date"
                                        >
                                            ✕ Remove
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            <tr x-show="dates.length === 0">
                                <td colspan="4" class="px-5 py-10 text-center text-slate-400">
                                    <span class="text-2xl block mb-1">📅</span>
                                    <p class="font-bold text-xs text-slate-600">No holiday dates configured.</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Click the <strong>"+ Add Holiday"</strong> button above to add dates.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 bg-slate-50/60 p-4 flex items-center justify-between">
                    <button
                        type="button"
                        @click="addDate()"
                        class="inline-flex items-center gap-1.5 text-xs font-extrabold text-brand-ink hover:underline"
                    >
                        <span class="text-emerald-600 font-black">+</span> Add another holiday date
                    </button>
                </div>
            </div>

            <!-- Sticky / Bottom Action Bar -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a
                    href="{{ route('admin.holiday-calendars.index') }}"
                    class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    class="rounded-xl px-6 py-2.5 text-xs font-extrabold text-white shadow-sm transition hover:opacity-95"
                    style="background-color: #CF5D38 !important; color: #ffffff !important;"
                >
                    Create Holiday Calendar
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
