@props([
    'name' => 'country',
    'id' => 'country',
    'selectedCode' => 'GB',
    'selectedName' => 'United Kingdom',
    'initialCountries' => [],
    'allCountries' => [],
])

@php
    $initialJson = json_encode(array_values($initialCountries), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $allJson = json_encode(array_values($allCountries), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $quickStoreUrl = route('admin.country-calling-codes.quick-store');
@endphp

<div
    class="relative"
    x-data="{
        open: false,
        search: '',
        selectedCode: '{{ $selectedCode }}',
        selectedName: '{{ $selectedName }}',
        selectedFlag: '',
        initialCountries: {{ $initialJson }},
        allCountries: {{ $allJson }},
        savingNew: false,

        init() {
            // Find initial flag
            const found = this.allCountries.find(c => c.code === this.selectedCode) 
                       || this.initialCountries.find(c => c.code === this.selectedCode);
            if (found) {
                this.selectedFlag = found.flag || '';
                this.selectedName = found.name;
            }
        },

        get displayValue() {
            return (this.selectedCode && this.selectedName) 
                ? (this.selectedCode + '|' + this.selectedName) 
                : '';
        },

        get filteredCountries() {
            const query = this.search.trim().toLowerCase();
            if (!query) {
                // If search is empty, show only the top 5-10 initial master data countries
                return this.initialCountries.slice(0, 10);
            }

            // When user types, filter all countries in real-time, limited to top 10 results
            return this.allCountries.filter(c => {
                return c.name.toLowerCase().includes(query) || c.code.toLowerCase().includes(query);
            }).slice(0, 10);
        },

        isInMasterData(code) {
            return this.initialCountries.some(c => c.code === code);
        },

        async selectCountry(c) {
            this.selectedCode = c.code;
            this.selectedName = c.name;
            this.selectedFlag = c.flag;
            this.open = false;
            this.search = '';

            // Update hidden input and notify Alpine parent
            const hiddenInput = this.$refs.hiddenInput;
            if (hiddenInput) {
                hiddenInput.value = this.displayValue;
                hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // If not yet in master data, register it via background quick-store
            if (!this.isInMasterData(c.code)) {
                this.savingNew = true;
                try {
                    const res = await fetch('{{ $quickStoreUrl }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']')?.content || ''
                        },
                        body: JSON.stringify({
                            iso_code: c.code,
                            country_name: c.name
                        })
                    });
                    if (res.ok) {
                        const data = await res.json();
                        if (data.success && data.country) {
                            this.initialCountries.push(data.country);
                        }
                    }
                } catch (e) {
                    console.error('Auto-registration in master data error:', e);
                } finally {
                    this.savingNew = false;
                }
            }
        }
    }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>
    <!-- Hidden Form Input that posts CODE|Name -->
    <input
        type="hidden"
        name="{{ $name }}"
        id="{{ $id }}"
        x-ref="hiddenInput"
        :value="displayValue"
    >

    <!-- Trigger Button (Matches admin-input style) -->
    <button
        type="button"
        @click="open = !open; if(open) { $nextTick(() => $refs.searchInput.focus()); }"
        class="admin-input flex w-full items-center justify-between gap-2 text-left text-xs bg-white cursor-pointer transition hover:border-slate-400"
        :class="{ 'ring-2 ring-brand-blue/20 border-brand-blue': open }"
    >
        <span class="inline-flex items-center gap-2 truncate">
            <span class="text-base leading-none" x-text="selectedFlag"></span>
            <span class="font-bold text-slate-800" x-text="selectedName"></span>
            <span class="text-[10px] font-semibold text-slate-400" x-text="'(' + selectedCode + ')'"></span>
        </span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Dropdown Menu -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 mt-1.5 w-full rounded-2xl border border-slate-200 bg-white p-2 shadow-xl"
        style="display: none;"
    >
        <!-- Search Input -->
        <div class="relative mb-2">
            <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
            <input
                type="text"
                x-ref="searchInput"
                x-model="search"
                placeholder="Type to search country name or code..."
                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-8 pr-3 text-xs font-medium text-slate-800 placeholder-slate-400 focus:border-brand-blue focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-blue"
            >
            <button
                type="button"
                x-show="search.length > 0"
                @click="search = ''; $refs.searchInput.focus();"
                class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-xs font-bold text-slate-400 hover:text-slate-600"
            >
                ✕
            </button>
        </div>

        <!-- Section Label -->
        <div class="px-2 py-1 text-[10px] font-black uppercase tracking-wider text-slate-400 flex items-center justify-between">
            <span x-text="search.trim() ? 'Matching Results (max 10)' : 'Master Data Countries (' + initialCountries.length + ')'"></span>
            <span x-show="!search.trim()" class="text-[9px] font-semibold text-slate-400 lowercase">type to search all 240+</span>
        </div>

        <!-- Country Options List -->
        <div class="max-h-60 overflow-y-auto space-y-0.5 divide-y divide-slate-50">
            <template x-for="c in filteredCountries" :key="c.code">
                <button
                    type="button"
                    @click="selectCountry(c)"
                    class="group flex w-full items-center justify-between rounded-xl px-2.5 py-2 text-left text-xs transition hover:bg-slate-100"
                    :class="{ 'bg-brand-blue/5 text-brand-blue font-bold': selectedCode === c.code }"
                >
                    <div class="flex items-center gap-2 truncate">
                        <span class="text-base leading-none" x-text="c.flag"></span>
                        <span class="truncate font-medium text-slate-800 group-hover:text-brand-ink" x-text="c.name"></span>
                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 group-hover:bg-slate-200" x-text="c.code"></span>
                    </div>

                    <div class="shrink-0 flex items-center gap-1.5">
                        <template x-if="isInMasterData(c.code)">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-bold text-slate-600">
                                In Master Data
                            </span>
                        </template>
                        <template x-if="!isInMasterData(c.code)">
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[9px] font-bold text-emerald-700">
                                <span>+ Add to Master</span>
                            </span>
                        </template>
                        <span x-show="selectedCode === c.code" class="text-brand-blue font-black ml-1">✓</span>
                    </div>
                </button>
            </template>

            <!-- Empty State -->
            <div x-show="filteredCountries.length === 0" class="py-6 text-center text-xs text-slate-400">
                No matching countries found for "<span class="font-bold text-slate-600" x-text="search"></span>"
            </div>
        </div>
    </div>
</div>
