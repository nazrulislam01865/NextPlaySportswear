<x-layouts.admin title="Remote Area Surcharges" subtitle="Manage indexed UPS remote/extended-area delivery rules and checkout extra charges." :compact-header="true">
    <div
        x-data="adminRemoteAreaImporter({
            startUrl: @js(route('admin.rural-area-surcharges.import.start')),
            chunkUrl: @js(route('admin.rural-area-surcharges.import.chunk')),
            finishUrl: @js(route('admin.rural-area-surcharges.import.finish')),
            indexUrl: @js(route('admin.rural-area-surcharges.index')),
        })"
        class="space-y-4"
    >
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <form method="GET" action="{{ route('admin.rural-area-surcharges.index') }}" class="grid flex-1 gap-2 sm:grid-cols-2 xl:grid-cols-[minmax(220px,1.6fr)_minmax(180px,1fr)_minmax(220px,1.2fr)_minmax(140px,.7fr)_auto]">
                <input
                    type="search"
                    name="search"
                    value="{{ $filters['search'] }}"
                    class="admin-input"
                    placeholder="Country, city, postcode, surcharge..."
                    aria-label="Search remote area surcharges"
                >
                <select name="iata_code" class="admin-input" aria-label="Filter by country">
                    <option value="">All countries</option>
                    @foreach($countryOptions as $country)
                        <option value="{{ $country->iata_code }}" @selected($filters['iataCode'] === $country->iata_code)>
                            {{ $country->country }} ({{ $country->iata_code }})
                        </option>
                    @endforeach
                </select>
                <select name="destination_surcharge" class="admin-input" aria-label="Filter by destination surcharge">
                    <option value="">All destination types</option>
                    @foreach($destinationOptions as $option)
                        <option value="{{ $option }}" @selected($filters['destination'] === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <select name="status" class="admin-input" aria-label="Filter by status">
                    <option value="">All statuses</option>
                    <option value="active" @selected($filters['status'] === 'active')>Active</option>
                    <option value="inactive" @selected($filters['status'] === 'inactive')>Inactive</option>
                </select>
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-white">Filter</button>
                    @if(collect($filters)->filter()->isNotEmpty())
                        <a href="{{ route('admin.rural-area-surcharges.index') }}" class="btn btn-white">Clear</a>
                    @endif
                </div>
            </form>

            <div class="flex shrink-0 flex-wrap gap-2">
                <button type="button" class="btn btn-white" @click="importOpen = !importOpen" :aria-expanded="importOpen.toString()">
                    Import UPS XLSX
                </button>
                <a href="{{ route('admin.rural-area-surcharges.create') }}" class="btn btn-red">+ Add Surcharge</a>
            </div>
        </div>

        <div x-cloak x-show="importOpen" x-transition.opacity class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-4 xl:grid-cols-[minmax(260px,1.5fr)_180px_160px_auto] xl:items-end">
                <label class="admin-label">
                    UPS remote-area workbook
                    <input
                        x-ref="fileInput"
                        type="file"
                        accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        class="admin-input"
                        @change="selectFile($event)"
                        :disabled="busy"
                    >
                </label>
                <label class="admin-label">
                    Extra charge
                    <input type="number" x-model="extraCharge" class="admin-input" min="0" max="999999.99" step="0.01" :disabled="busy" required>
                </label>
                <label class="admin-label">
                    Carrier
                    <input type="text" x-model="carrier" class="admin-input uppercase" maxlength="40" :disabled="busy" required>
                </label>
                <button type="button" class="btn btn-red" @click="runImport()" :disabled="busy || !file">
                    <span x-text="busy ? 'Importing...' : 'Import workbook'"></span>
                </button>
            </div>

            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <label class="flex items-center gap-2 text-xs font-medium text-slate-600">
                    <input type="checkbox" x-model="replaceExisting" class="h-4 w-4 rounded border-slate-300 text-brand-red" :disabled="busy">
                    Replace previous imported rows for this carrier; keep manual rules.
                </label>
                <p class="text-xs text-slate-500">Expected headers: Country, IATA Code, Low, High, City, Origin Surcharge, Destination Surcharge.</p>
            </div>

            <template x-if="busy || progress > 0">
                <div class="mt-3">
                    <div class="mb-1 flex items-center justify-between text-xs font-medium text-slate-600">
                        <span x-text="statusText || 'Preparing import...'"></span>
                        <span x-text="`${Math.round(progress)}%`"></span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-brand-red transition-all duration-200" :style="`width:${Math.max(0, Math.min(100, progress))}%`"></div>
                    </div>
                </div>
            </template>

            <p x-cloak x-show="error" class="mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700" x-text="error"></p>
            <p x-cloak x-show="success" class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700" x-text="success"></p>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="admin-table-scroll" tabindex="0" aria-label="Remote area surcharge table">
                <table class="admin-table min-w-[1100px]">
                    <thead>
                        <tr>
                            <th>Country</th>
                            <th>IATA</th>
                            <th>Postal range / City</th>
                            <th>Origin surcharge</th>
                            <th>Destination surcharge</th>
                            <th>Extra charge</th>
                            <th>Status</th>
                            <th>Source</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($surcharges as $surcharge)
                            <tr>
                                <td>
                                    <strong class="block text-brand-ink">{{ $surcharge->country ?: 'Any country' }}</strong>
                                    <span class="block text-slate-500">{{ $surcharge->carrier ?: 'UPS' }}</span>
                                </td>
                                <td class="font-semibold text-slate-700">{{ $surcharge->iata_code ?: 'Legacy' }}</td>
                                <td>
                                    @if($surcharge->city)
                                        <strong class="block text-brand-ink">{{ $surcharge->city }}</strong>
                                        <span class="block text-slate-500">City-based rule</span>
                                    @else
                                        <strong class="block text-brand-ink">{{ $surcharge->postalRangeLabel() ?: 'Legacy patterns' }}</strong>
                                        @if(!$surcharge->isStructuredRemoteAreaRecord())
                                            <span class="block max-w-[260px] truncate text-slate-500">{{ $surcharge->postal_code_patterns }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-slate-600">{{ $surcharge->origin_surcharge ?: 'Legacy rule' }}</td>
                                <td>
                                    <span class="admin-status-pill {{ $surcharge->destination_surcharge === 'No' ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-700' }}">
                                        {{ $surcharge->destination_surcharge ?: 'Legacy rule' }}
                                    </span>
                                </td>
                                <td class="font-semibold text-brand-ink">${{ number_format($surcharge->effectiveExtraCharge(), 2) }}</td>
                                <td>
                                    <span class="admin-status-pill {{ $surcharge->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $surcharge->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    @if($surcharge->source_file)
                                        <span class="block max-w-[180px] truncate font-medium text-slate-700" title="{{ $surcharge->source_file }}">Imported XLSX</span>
                                        <span class="block text-slate-500">Row {{ number_format((int) $surcharge->source_row) }}</span>
                                    @else
                                        <span class="text-slate-500">Manual</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="admin-row-actions justify-end">
                                        <a class="admin-row-action border-slate-200" href="{{ route('admin.rural-area-surcharges.edit', $surcharge) }}">Edit</a>
                                        <form method="POST" action="{{ route('admin.rural-area-surcharges.destroy', $surcharge) }}" onsubmit="return confirm('Delete this remote area surcharge?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-slate-500">No remote-area surcharge records match the current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $surcharges->links('pagination.nextplay') }}</div>
    </div>
</x-layouts.admin>
