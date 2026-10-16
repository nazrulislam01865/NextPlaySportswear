<x-layouts.admin :title="'Edit: ' . $calendar->name" subtitle="Define the country and year this calendar applies to, then add the holiday dates.">
    <div class="space-y-6" x-data="{
        country: '{{ old('country', $calendar->country_code . '|' . $calendar->country_name) }}',
        get countryName() {
            return this.country.includes('|') ? this.country.split('|')[1] : this.country;
        },
        isActive: {{ old('is_active', $calendar->is_active) ? 'true' : 'false' }},
        dates: {{ Js::from(old('dates', $dates)) }},
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
    }"
    @change="if ($event.target.name === 'country') { country = $event.target.value; }"
>
        <!-- Top Action Bar -->
        <div class="flex items-center justify-end">
            <a
                href="{{ route('admin.holiday-calendars.show', $calendar) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition"
            >
                <span>View Calendar</span>
            </a>
        </div>

        @if(session('status'))
            <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-sm">
                <span class="grid h-5 w-5 place-items-center rounded-full bg-emerald-200 text-xs font-black text-emerald-800">✓</span>
                <span>{{ session('status') }}</span>
            </div>
        @endif

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

        <form method="POST" action="{{ route('admin.holiday-calendars.update', $calendar) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Card 1: Calendar Details (Matching Screen 2 of Blueprint) -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <div>
                    <h2 class="text-sm font-extrabold text-brand-ink">Calendar Details</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Define the country and year this calendar applies to.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
                    <!-- Calendar name -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5" for="name">
                            Calendar name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name', $calendar->name) }}"
                            placeholder="e.g. UK Public Holidays 2026"
                            class="admin-input text-xs w-full @error('name') border-rose-400 @enderror"
                            required
                        >
                        @error('name')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Country -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5" for="country">
                            Country <span class="text-rose-500">*</span>
                        </label>
                        <x-admin.searchable-country-select
                            id="country"
                            name="country"
                            :selected-code="explode('|', old('country', $calendar->country_code . '|' . $calendar->country_name))[0]"
                            :selected-name="explode('|', old('country', $calendar->country_code . '|' . $calendar->country_name))[1] ?? $calendar->country_name"
                            :initial-countries="$countries"
                            :all-countries="$catalogCountries"
                        />
                        @error('country_code')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Calendar year -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5" for="year">
                            Calendar year <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="number"
                            id="year"
                            name="year"
                            value="{{ old('year', $calendar->year) }}"
                            min="2020"
                            max="2050"
                            class="admin-input text-xs w-full @error('year') border-rose-400 @enderror"
                            required
                        >
                        @error('year')
                            <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status Toggle -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Status</label>
                        <div class="flex items-center gap-3 py-1.5">
                            <input type="hidden" name="is_active" :value="isActive ? 1 : 0">
                            <button
                                type="button"
                                @click="isActive = !isActive"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="isActive ? 'bg-emerald-600' : 'bg-slate-300'"
                                role="switch"
                                :aria-checked="isActive"
                            >
                                <span
                                    aria-hidden="true"
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    :class="isActive ? 'translate-x-5' : 'translate-x-0'"
                                ></span>
                            </button>
                            <span class="text-xs font-bold text-slate-700" x-text="isActive ? 'Active' : 'Inactive'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Holiday Dates Dynamic Table (Matching Screen 2 of Blueprint) -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <div>
                    <h2 class="text-sm font-extrabold text-brand-ink">Holiday Dates</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Dates when normal delivery is unavailable for this country.</p>
                </div>

                <div class="space-y-3">
                    <div class="grid grid-cols-[200px_1fr_120px] gap-4 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 px-1">
                        <div>Date</div>
                        <div>Holiday name</div>
                        <div class="text-right">Actions</div>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(item, index) in dates" :key="index">
                            <div class="grid grid-cols-[200px_1fr_120px] gap-4 items-center">
                                <div>
                                    <input
                                        type="date"
                                        :name="'dates[' + index + '][date]'"
                                        x-model="item.date"
                                        class="admin-input text-xs font-semibold py-2 w-full"
                                        required
                                    >
                                </div>
                                <div>
                                    <input
                                        type="text"
                                        :name="'dates[' + index + '][name]'"
                                        x-model="item.name"
                                        placeholder="e.g. Christmas Day"
                                        class="admin-input text-xs py-2 w-full"
                                        required
                                    >
                                </div>
                                <div class="text-right">
                                    <button
                                        type="button"
                                        @click="removeDate(index)"
                                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm transition"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="pt-2">
                        <button
                            type="button"
                            @click="addDate()"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-sm transition"
                        >
                            <span class="text-sm leading-none font-bold">+</span>
                            <span>Add Holiday</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Delivery Impact Callout (Matching Screen 2 of Blueprint) -->
            <div class="rounded-2xl border border-amber-300 bg-amber-50/90 p-5 shadow-sm flex items-start gap-3.5">
                <span class="grid h-5 w-5 place-items-center rounded-full bg-[#c2410c] text-xs font-black text-white shrink-0 mt-0.5">
                    i
                </span>
                <div class="text-xs text-amber-950">
                    <strong class="font-extrabold block text-xs mb-0.5">Delivery impact</strong>
                    <p class="leading-relaxed text-amber-900 font-medium">
                        Changes to an active holiday calendar may update estimated delivery dates for open orders shipping to <span class="font-bold" x-text="countryName">{{ $calendar->country_name }}</span>. Customers whose estimate changes will be notified.
                    </p>
                </div>
            </div>

            <!-- Footer Action Buttons (Matching Screen 2 of Blueprint) -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a
                    href="{{ route('admin.holiday-calendars.index') }}"
                    class="rounded-xl border border-slate-300 bg-white px-6 py-2.5 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50 transition"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    class="rounded-xl px-6 py-2.5 text-xs font-bold text-white shadow-sm transition hover:opacity-95"
                    style="background-color: #CF5D38 !important; color: #ffffff !important;"
                >
                    Update Holiday Calendar
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
