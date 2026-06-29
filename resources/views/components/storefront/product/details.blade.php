@props(['product'])

@php
    $sizeGroupsWithCharts = collect($product['size_groups'] ?? [])
        ->filter(function ($group) {
            $chart = $group['chart'] ?? [];

            return (bool) ($chart['enabled'] ?? false)
                || filled($chart['html'] ?? null)
                || filled($chart['image'] ?? null)
                || (! empty($chart['columns'] ?? []) && ! empty($chart['rows'] ?? []));
        })
        ->values();
    $firstSizeChartId = data_get($sizeGroupsWithCharts->first(), 'id');
@endphp

<section class="section-padding bg-white" x-data="{ tab: 'description', sizeChart: @js($firstSizeChartId) }">
    <div class="site-container overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-card sm:rounded-[28px]">
        <div class="grid grid-cols-3 border-b border-slate-200 bg-slate-50">
            @foreach(['description'=>'Description','specifications'=>'Specifications','faq'=>'FAQ'] as $key => $label)
                <button type="button" @click="tab='{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-white text-brand-red shadow-[inset_0_-3px_0_#e91d33]' : 'text-slate-600'" class="min-h-12 min-w-0 border-r border-slate-200 px-2 py-3 text-[11px] font-black leading-tight last:border-r-0 sm:px-5 sm:py-4 sm:text-sm">{{ $label }}</button>
            @endforeach
        </div>
        <div class="p-4 sm:p-6">
            <div x-show="tab === 'description'" class="space-y-6">
                <div class="product-rich-content">{!! $product['description_html'] !!}</div>

                @if($sizeGroupsWithCharts->isNotEmpty())
                    <div class="np-selected-size-chart border-t border-slate-200 pt-5">
                        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Size guide</p>
                                <h3 class="mt-1 text-xl font-black text-brand-ink">Selected Size Chart</h3>
                            </div>

                            @if($sizeGroupsWithCharts->count() > 1)
                                <div class="flex flex-wrap gap-2 rounded-2xl bg-slate-100 p-1">
                                    @foreach($sizeGroupsWithCharts as $group)
                                        <button type="button" class="rounded-xl px-4 py-2 text-xs font-black uppercase tracking-wide transition" :class="sizeChart === @js($group['id']) ? 'bg-white text-brand-red shadow-sm' : 'text-slate-600 hover:text-brand-ink'" @click="sizeChart = @js($group['id'])">
                                            {{ $group['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @foreach($sizeGroupsWithCharts as $group)
                            @php $chart = $group['chart'] ?? []; @endphp
                            <div x-show="sizeChart === @js($group['id'])" @if(! $loop->first) x-cloak @endif class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 sm:px-5">
                                    <h4 class="text-sm font-black text-brand-ink">{{ $chart['title'] ?? ($group['label'].' Size Chart') }}</h4>
                                    @if(filled($chart['note'] ?? null))
                                        <p class="mt-1 text-xs leading-5 text-slate-600">{{ $chart['note'] }}</p>
                                    @endif
                                </div>

                                <div class="space-y-4 p-3 sm:p-4">
                                    @if(filled($chart['html'] ?? null))
                                        <div class="product-rich-content touch-scroll-x">{!! $chart['html'] !!}</div>
                                    @endif

                                    @if(filled($chart['image'] ?? null))
                                        <img src="{{ $chart['image'] }}" alt="{{ $chart['title'] ?? ($group['label'].' size chart') }}" class="mx-auto max-h-[420px] w-full rounded-xl border border-slate-200 bg-white object-contain" loading="lazy" decoding="async">
                                    @endif

                                    @if(!empty($chart['columns'] ?? []) && !empty($chart['rows'] ?? []))
                                        <div class="touch-scroll-x rounded-xl border border-slate-200" tabindex="0">
                                            <table class="w-full min-w-[620px] text-[13px]">
                                                <thead class="bg-brand-dark text-left text-[11px] font-black uppercase tracking-[.08em] text-white">
                                                    <tr>
                                                        @foreach($chart['columns'] as $column)
                                                            <th class="px-3 py-2.5">{{ $column }}</th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100 bg-white">
                                                    @foreach($chart['rows'] as $row)
                                                        <tr class="odd:bg-slate-50">
                                                            @foreach($row as $cell)
                                                                <td class="px-3 py-2.5 font-semibold text-slate-700">{{ $cell }}</td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div x-show="tab === 'specifications'">
                @if(filled($product['detail_information_html'] ?? null))
                    <div class="product-rich-content">{!! $product['detail_information_html'] !!}</div>
                @else
                    <table class="w-full table-fixed border-collapse text-[13px] sm:text-sm"><tbody>@forelse($product['detail_information'] as $name => $value)<tr><th class="w-[42%] break-words border border-slate-200 bg-slate-50 p-2.5 text-left align-top font-black sm:w-1/3">{{ $name }}</th><td class="break-words border border-slate-200 p-2.5 align-top text-slate-600">{{ $value }}</td></tr>@empty<tr><td class="p-8 text-center text-slate-500">No specifications have been added.</td></tr>@endforelse</tbody></table>
                @endif
            </div>
            <div x-show="tab === 'faq'" class="space-y-3">@forelse($product['faqs'] as $faq)<details class="rounded-2xl border border-slate-200 bg-white p-4"><summary class="cursor-pointer list-none font-black text-brand-ink">{{ $faq['question'] }}</summary><p class="mt-3 text-sm leading-7 text-slate-600">{{ $faq['answer'] }}</p></details>@empty<p class="text-sm text-slate-500">No product-specific FAQs have been added.</p>@endforelse</div>
        </div>
    </div>
</section>
