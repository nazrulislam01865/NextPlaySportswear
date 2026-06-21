@props([
    'chart' => [],
])

@php
    $groups = $chart['groups'] ?? [];
@endphp

<section id="size-chart" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card lg:p-6" x-data="{ active: 0 }">
    <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Size guide</p>
            <h2 class="mt-1 font-display text-3xl font-bold uppercase tracking-tight text-brand-ink">{{ $chart['title'] ?? 'Size Chart' }}</h2>
            @if (! empty($chart['note']))
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ $chart['note'] }}</p>
            @endif
        </div>

        @if (count($groups) > 1)
            <div class="flex flex-wrap gap-2 rounded-2xl bg-slate-100 p-1">
                @foreach ($groups as $group)
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-xs font-black uppercase tracking-wide transition"
                        :class="active === {{ $loop->index }} ? 'bg-white text-brand-red shadow-sm' : 'text-slate-600 hover:text-brand-ink'"
                        @click="active = {{ $loop->index }}"
                    >
                        {{ $group['label'] }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @foreach ($groups as $group)
        <div class="mt-5" x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif>
            <div class="mb-3 grid gap-3 md:grid-cols-[220px_1fr]">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="mx-auto grid h-40 max-w-[150px] place-items-center rounded-[2rem] border-2 border-dashed border-brand-blue bg-white text-center text-xs font-black uppercase tracking-wide text-brand-blue">
                        Chest<br>Length<br>Sleeve
                    </div>
                    <p class="mt-3 text-center text-xs leading-5 text-slate-500">Compare with a jersey that already fits well.</p>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-brand-navy text-white">
                            <tr>
                                @foreach ($group['columns'] as $column)
                                    <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-black uppercase tracking-wide">{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($group['rows'] as $row)
                                <tr class="hover:bg-slate-50">
                                    @foreach ($row as $cell)
                                        <td class="whitespace-nowrap px-4 py-3 @if ($loop->first) font-black text-brand-ink @else text-slate-600 @endif">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach

    @if (! empty($chart['tips']))
        <div class="mt-4 grid gap-3 md:grid-cols-3">
            @foreach ($chart['tips'] as $tip)
                <div class="rounded-2xl bg-blue-50 p-3 text-xs font-semibold leading-5 text-brand-blue">
                    {{ $tip }}
                </div>
            @endforeach
        </div>
    @endif
</section>
