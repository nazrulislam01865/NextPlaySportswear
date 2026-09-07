@props([
    'roster' => [],
    'stepNumber' => 1,
    'hasSizeGroups' => false,
])

<section class="rounded-[28px] border border-slate-200 bg-white shadow-card" id="product-roster">
    <div class="flex items-start gap-4 border-b border-slate-200 bg-gradient-to-r from-white to-blue-50 p-5 sm:p-6">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-dark font-black text-white">{{ $stepNumber }}</span>
        <div class="min-w-0 flex-1">
            <h3 class="text-xl font-black leading-tight text-brand-ink sm:text-2xl">{{ $roster['title'] ?? 'Add item details' }}</h3>
            <p class="mt-1 text-sm leading-6 text-slate-500">
                Enter the configured details for each ordered item.
                @if($hasSizeGroups)
                    Each row is linked to its selected size.
                @else
                    The number of rows follows your order quantity.
                @endif
            </p>
        </div>
    </div>

    <div class="p-5 sm:p-6">
        @if($roster['optional'] ?? true)
            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <input type="checkbox" class="mt-1" :checked="rosterEnabled" @change="toggleRoster($event.target.checked)">
                <span>
                    <strong class="block text-sm text-brand-ink">Add individual details for each item</strong>
                    <small class="mt-1 block text-xs leading-5 text-slate-500">Turn this on to enter the fields configured for this product.</small>
                </span>
            </label>
        @endif

        <div x-show="rosterEnabled" x-cloak class="mt-5">
            <div x-show="totalQuantity() === 0" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">
                Choose the product quantity first. The roster rows will then appear here.
            </div>

            <div x-show="totalQuantity() > 250" class="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm font-bold text-amber-900">
                Individual roster details support up to 250 pieces in one configured cart line.
            </div>

            <div x-show="rosterRows.length" class="space-y-3">
                <template x-for="(row, rowIndex) in rosterRows" :key="`${row.size_key || 'item'}:${rowIndex}`">
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                            <strong class="text-sm text-brand-ink">Item <span x-text="rowIndex + 1"></span></strong>
                            @if($hasSizeGroups)
                                <span class="rounded-full bg-brand-dark px-3 py-1 text-xs font-black text-white">
                                    <span x-text="row.size_group_label"></span> · <span x-text="row.size_label"></span>
                                </span>
                            @endif
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach(collect($roster['fields'] ?? [])->filter(fn ($field) => ($field['enabled'] ?? true)) as $field)
                                <label class="text-xs font-black uppercase tracking-[.08em] text-slate-500">
                                    {{ $field['label'] }} @if($field['required'] ?? false)<span class="text-brand-red">*</span>@endif
                                    <input
                                        class="mt-2 h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold normal-case tracking-normal text-brand-ink"
                                        type="text"
                                        @if(($field['type'] ?? 'text') === 'number') inputmode="numeric" @endif
                                        maxlength="{{ min(120, max(1, (int) ($field['max_length'] ?? 60))) }}"
                                        x-model="row.values[@js($field['key'])]"
                                        @input="sync()"
                                        @change="commitRosterField(rowIndex, @js($field), $event.target.value)"
                                        placeholder="{{ $field['label'] }}"
                                    >
                                </label>
                            @endforeach
                        </div>
                    </article>
                </template>
            </div>
        </div>
    </div>
</section>
