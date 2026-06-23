@props([
    'product' => [],
])

@php
    $groups = $product['size_quantity_groups'] ?? [];
    $selector = $product['size_selector'] ?? [];
    $chart = $product['size_chart'] ?? [];
    $basePrice = (float) ($selector['base_price'] ?? $product['base_price'] ?? 0);
@endphp

<section
    id="choose-sizes"
    class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-hero"
    x-data="{
        basePrice: {{ $basePrice }},
        sizeChartOpen: false,
        quantities: {},
        totalQty() {
            return Object.values(this.quantities).reduce((sum, value) => sum + (Number(value) || 0), 0);
        },
        totalPrice() {
            return (this.totalQty() * this.basePrice).toFixed(2);
        }
    }"
>
    <div class="grid min-w-0 bg-slate-200 sm:grid-cols-[150px_minmax(0,1fr)] lg:grid-cols-[190px_minmax(0,1fr)]">
        <div class="bg-fuchsia-600 px-5 py-5 text-white sm:py-6 lg:px-8">
            <span class="block text-xs font-black uppercase tracking-[.22em] text-fuchsia-100">Step</span>
            <span class="mt-1 block text-4xl font-black leading-none lg:text-5xl">{{ $selector['step'] ?? '07' }}</span>
        </div>

        <div class="flex min-w-0 flex-col gap-4 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <div class="min-w-0">
                <p class="text-xs font-black uppercase tracking-[.18em] text-slate-500">Team roster sizing</p>
                <h2 class="mt-1 text-2xl font-black leading-tight text-brand-ink sm:text-4xl">{{ $selector['title'] ?? 'Choose Your Sizes' }}</h2>
            </div>

            <div class="grid w-full min-w-0 gap-3 sm:grid-cols-2 lg:w-auto lg:min-w-[380px]">
                <button
                    type="button"
                    class="inline-flex min-h-[54px] items-center justify-center rounded-2xl border-2 border-brand-red bg-white px-5 text-center text-sm font-black text-brand-ink shadow-sm transition hover:-translate-y-0.5 hover:bg-red-50"
                    @click="sizeChartOpen = true"
                >
                    View Size Chart
                </button>
                <div class="flex min-h-[54px] items-center justify-center rounded-2xl bg-white px-5 text-center text-sm font-black text-brand-ink shadow-sm sm:text-base">
                    Total Qty: <span class="mx-1 text-brand-red" x-text="totalQty()">0</span>
                    <span class="mx-1 text-slate-300">|</span>
                    Price: $<span x-text="totalPrice()">0.00</span>
                </div>
            </div>
        </div>
    </div>

    <div class="p-5 sm:p-6 lg:p-8">
        @if (! empty($selector['note']))
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-center text-sm font-semibold leading-6 text-slate-700 sm:text-base">
                {{ $selector['note'] }}
            </div>
        @endif

        <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white">
            @foreach ($groups as $group)
                <details class="group border-b border-slate-200 last:border-b-0" open>
                    <summary class="flex cursor-pointer list-none items-center gap-3 bg-white px-4 py-4 transition hover:bg-slate-50">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-slate-100 text-brand-ink transition group-open:rotate-180">⌄</span>
                        <span class="flex-1 text-center text-xl font-black text-brand-ink">{{ $group['label'] }}</span>
                    </summary>

                    <div class="px-4 pb-5">
                        <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(78px,1fr))] sm:[grid-template-columns:repeat(auto-fit,minmax(92px,1fr))]">
                            @foreach ($group['sizes'] as $size)
                                @php
                                    $fieldKey = $group['key'] . '-' . \Illuminate\Support\Str::slug($size);
                                @endphp
                                <label class="min-w-0 rounded-2xl border border-slate-200 bg-slate-50 p-3 text-center focus-within:shadow-soft focus-within:border-brand-red focus-within:bg-white">
                                    <span class="mb-2 block truncate text-sm font-black uppercase tracking-wide text-brand-red" title="{{ $size }}">{{ $size }}</span>
                                    <input
                                        type="number"
                                        name="sizes[{{ $group['key'] }}][{{ $size }}]"
                                        min="0"
                                        inputmode="numeric"
                                        placeholder="Qty"
                                        x-model.number="quantities['{{ $fieldKey }}']"
                                        class="h-11 w-full rounded-xl border border-slate-300 bg-white px-2 text-center text-sm font-bold text-brand-ink outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-blue-100"
                                    >
                                </label>
                            @endforeach
                        </div>
                    </div>
                </details>
            @endforeach
        </div>

        <div class="mt-5 grid gap-4 rounded-3xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-2 lg:p-5">
            <label class="grid gap-2 text-sm font-black text-slate-800">
                Roster / size file
                <input type="file" name="size_roster" class="w-full rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-600 file:mr-3 file:rounded-xl file:border-0 file:bg-brand-ink file:px-4 file:py-2 file:text-sm file:font-black file:text-white">
                <span class="text-xs font-medium leading-5 text-slate-500">Optional: upload player names, numbers, and sizes in one file.</span>
            </label>

            <label class="grid gap-2 text-sm font-black text-slate-800">
                Custom size notes
                <textarea name="size_notes" rows="3" class="min-h-[104px] rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-blue-100" placeholder="Example: #10 John - custom length, #23 needs loose fit..."></textarea>
            </label>
        </div>
    </div>

    <div
        class="fixed inset-0 z-50 grid place-items-center bg-slate-950/60 p-2 sm:p-4"
        x-cloak
        x-show="sizeChartOpen"
        x-transition.opacity
        @keydown.escape.window="sizeChartOpen = false"
    >
        <div class="max-h-[96dvh] w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-hero sm:max-h-[90vh] sm:rounded-3xl" @click.outside="sizeChartOpen = false">
            <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-5 sm:py-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Size guide</p>
                    <h3 class="text-xl font-black text-brand-ink sm:text-2xl">{{ $chart['title'] ?? 'Size Chart' }}</h3>
                    @if (! empty($chart['note']))
                        <p class="mt-1 text-sm text-slate-500">{{ $chart['note'] }}</p>
                    @endif
                </div>
                <button type="button" class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100 text-xl font-black text-brand-ink hover:bg-red-50 hover:text-brand-red" @click="sizeChartOpen = false">×</button>
            </div>

            <div class="max-h-[78dvh] overflow-y-auto p-3 sm:max-h-[72vh] sm:p-5">
                <div class="grid gap-5">
                    @foreach (($chart['groups'] ?? []) as $group)
                        <div class="overflow-hidden rounded-2xl border border-slate-200">
                            <div class="bg-brand-navy px-4 py-3 text-sm font-black text-white">{{ $group['label'] }}</div>
                            <div class="touch-scroll-x">
                                <table class="min-w-full text-left text-sm">
                                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                        <tr>
                                            @foreach ($group['columns'] as $column)
                                                <th class="whitespace-nowrap px-4 py-3 font-black">{{ $column }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($group['rows'] as $row)
                                            <tr>
                                                @foreach ($row as $cell)
                                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">{{ $cell }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if (! empty($chart['tips']))
                    <div class="mt-5 grid gap-2 rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-600 sm:grid-cols-3">
                        @foreach ($chart['tips'] as $tip)
                            <div class="flex gap-2">
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-brand-red"></span>
                                <span>{{ $tip }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
