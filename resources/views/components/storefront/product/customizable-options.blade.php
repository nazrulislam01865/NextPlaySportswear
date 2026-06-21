@props([
    'options' => [],
])

@php
    $initialSelections = collect($options)
        ->mapWithKeys(fn (array $option): array => [
            $option['id'] => $option['values'][0]['value'] ?? null,
        ])
        ->toArray();
@endphp

<section id="design-options" class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-hero" x-data="{ selections: @js($initialSelections) }">
    <div class="border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-red-50 px-5 py-5 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Custom build flow</p>
                <h2 class="mt-1 text-3xl font-black tracking-tight text-brand-ink sm:text-4xl">Product Options</h2>
                <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-600">
                    Choose the first admin-controlled customizable option. Later the admin can add more option groups without changing this product page layout.
                </p>
            </div>

            <div class="flex w-fit items-center gap-2 rounded-full bg-white px-4 py-2 text-xs font-black text-brand-blue shadow-sm ring-1 ring-blue-100">
                <span class="h-2 w-2 rounded-full bg-green-500"></span>
                Admin-ready component
            </div>
        </div>
    </div>

    <form class="grid gap-6 p-5 sm:p-6 lg:p-8" action="{{ route('quote.request') }}" method="GET" enctype="multipart/form-data">
        @forelse ($options as $option)
            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex gap-3">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-navy text-sm font-black text-white shadow-sm">
                            {{ $option['step'] ?? str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-2xl font-black text-brand-ink">{{ $option['title'] }}</h3>
                                @if ($option['required'] ?? false)
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-brand-red">Required</span>
                                @endif
                            </div>
                            @if (! empty($option['description']))
                                <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-600">{{ $option['description'] }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    @foreach ($option['values'] as $value)
                        @php
                            $inputId = $option['id'] . '-' . $value['value'];
                        @endphp

                        <label
                            for="{{ $inputId }}"
                            class="group relative flex min-h-[230px] cursor-pointer flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:border-brand-blue hover:shadow-soft"
                            :class="selections['{{ $option['id'] }}'] === '{{ $value['value'] }}' ? 'ring-2 ring-brand-red border-brand-red shadow-soft' : ''"
                        >
                            <input
                                id="{{ $inputId }}"
                                type="radio"
                                class="sr-only"
                                name="options[{{ $option['id'] }}]"
                                value="{{ $value['value'] }}"
                                @checked($loop->first)
                                @change="selections['{{ $option['id'] }}'] = '{{ $value['value'] }}'"
                            >

                            <span class="absolute right-4 top-4 rounded-full bg-white/90 px-3 py-1 text-[11px] font-black uppercase text-slate-700 shadow-sm">
                                {{ $value['badge'] ?? $value['price_delta'] }}
                            </span>
                            <span class="block h-28 rounded-2xl border border-slate-200 shadow-inner" style="background: {{ $value['preview'] }}"></span>

                            <span class="mt-4 flex flex-1 flex-col">
                                <span class="text-xl font-black leading-tight text-brand-ink">{{ $value['label'] }}</span>
                                <span class="mt-2 text-sm font-medium leading-6 text-slate-600">{{ $value['description'] }}</span>
                            </span>

                            <span class="mt-4 flex items-center justify-between rounded-2xl bg-slate-50 px-3 py-3 text-sm font-black text-slate-700">
                                <span>Price impact</span>
                                <span class="text-brand-red">{{ $value['price_delta'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                @if (! empty($option['help']))
                    <p class="mt-5 rounded-2xl bg-white px-4 py-3 text-sm font-semibold leading-6 text-slate-600 ring-1 ring-slate-200">
                        {{ $option['help'] }}
                    </p>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500">
                No customizable option has been configured yet.
            </div>
        @endforelse

        <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="grid gap-5 lg:grid-cols-2">
                <label class="grid gap-2 text-sm font-black text-slate-800">
                    Artwork / logo file
                    <input type="file" name="artwork" class="w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-600 file:mr-3 file:rounded-xl file:border-0 file:bg-brand-ink file:px-4 file:py-2 file:text-sm file:font-black file:text-white">
                    <span class="text-xs font-medium leading-5 text-slate-500">Upload logo, reference design, AI/PDF/PNG/JPG file, or send later during proof review.</span>
                </label>

                <label class="grid gap-2 text-sm font-black text-slate-800">
                    Preferred delivery
                    <select name="delivery" class="h-[58px] rounded-2xl border border-slate-300 bg-white px-4 text-sm font-bold text-brand-ink outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-blue-100">
                        <option value="standard">Standard production</option>
                        <option value="rush">Rush delivery quote</option>
                        <option value="sample">Order sample first</option>
                        <option value="pay-later-proof">See design / pay after proof</option>
                    </select>
                    <span class="text-xs font-medium leading-5 text-slate-500">Production date will be confirmed after proof approval.</span>
                </label>
            </div>

            <label class="mt-5 grid gap-2 text-sm font-black text-slate-800">
                Design notes
                <textarea name="notes" rows="4" class="min-h-[150px] rounded-2xl border border-slate-300 bg-white px-4 py-4 text-sm font-semibold leading-6 outline-none transition focus:border-brand-blue focus:ring-4 focus:ring-blue-100" placeholder="Team colors, logo placement, sleeve/collar notes, old order reference, or any special instruction..."></textarea>
            </label>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <button type="submit" class="btn btn-red min-h-[56px] text-base">Save Design Options</button>
            <a href="#choose-sizes" class="btn btn-light min-h-[56px] text-base">Choose Sizes</a>
        </div>
    </form>
</section>
