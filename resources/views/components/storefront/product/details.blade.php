@props(['product'])

@php
    $customizationArtworkHtml = trim((string) ($product['customization_artwork_html'] ?? ''));
    $fulfillmentHtml = trim((string) ($product['fulfillment_html'] ?? ''));
    $customizationGroups = collect($product['option_groups'] ?? [])
        ->filter(fn ($group) => ($group['display_mode'] ?? 'customer') === 'customer')
        ->filter(fn ($group) => filled($group['label'] ?? null) || collect($group['values'] ?? [])->isNotEmpty())
        ->values();
    $artworkUpload = $product['artwork_upload'] ?? [];
    $hasGeneratedCustomizationGuidance = $customizationGroups->isNotEmpty() || (bool) ($artworkUpload['enabled'] ?? false);
    $fullDetailInformation = collect($product['detail_information'] ?? [])
        ->filter(fn ($value, $label) => filled($label) && filled($value));
@endphp

<section class="section-padding bg-white" x-data="{ tab: 'description' }">
    <div class="site-container overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-card sm:rounded-[28px]">
        <div class="np-product-detail-tabs flex overflow-x-auto border-b border-slate-200 bg-slate-50">
            @foreach(['description'=>'Description','specifications'=>'Specifications','customization'=>'Customization & Artwork','fulfillment'=>'Fulfillment','faq'=>'FAQ'] as $key => $label)
                <button type="button" @click="tab='{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-white text-brand-red shadow-[inset_0_-3px_0_#e91d33]' : 'text-slate-600'" class="np-product-detail-tab min-h-12 min-w-[132px] flex-1 border-r border-slate-200 px-3 py-3 text-[11px] font-black leading-tight last:border-r-0 sm:px-5 sm:py-4 sm:text-sm">{{ $label }}</button>
            @endforeach
        </div>
        <div class="p-4 sm:p-6">
            <div x-show="tab === 'description'" class="space-y-6">
                <div class="product-rich-content">{!! $product['description_html'] !!}</div>
            </div>

            <div x-show="tab === 'specifications'">
                @if($fullDetailInformation->isNotEmpty())
                    <table class="w-full table-fixed border-collapse text-[13px] sm:text-sm">
                        <tbody>
                            @foreach($fullDetailInformation as $name => $value)
                                <tr>
                                    <th class="w-[42%] break-words border border-slate-200 bg-slate-50 p-2.5 text-left align-top font-black sm:w-1/3">{{ $name }}</th>
                                    <td class="break-words border border-slate-200 p-2.5 align-top text-slate-600">{{ $value }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @elseif(filled($product['detail_information_html'] ?? null))
                    <div class="product-rich-content">{!! $product['detail_information_html'] !!}</div>
                @else
                    <p class="p-8 text-center text-sm text-slate-500">No specifications have been added.</p>
                @endif
            </div>

            <div x-show="tab === 'customization'" class="space-y-5">
                @if($customizationArtworkHtml !== '')
                    <div class="product-rich-content">{!! $customizationArtworkHtml !!}</div>
                @elseif($hasGeneratedCustomizationGuidance)
                    @if($customizationGroups->isNotEmpty())
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach($customizationGroups as $group)
                                @php $values = collect($group['values'] ?? [])->filter(fn ($value) => filled($value['label'] ?? null))->values(); @endphp
                                <article class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <h3 class="text-base font-black text-brand-ink">{{ $group['label'] ?? 'Customization option' }}</h3>
                                    @if(filled($group['description'] ?? null))
                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $group['description'] }}</p>
                                    @endif
                                    @if($values->isNotEmpty())
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach($values as $value)
                                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-black text-slate-700">{{ $value['label'] }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @endif

                    @if((bool) ($artworkUpload['enabled'] ?? false))
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                            <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Artwork & design guideline</p>
                            <h3 class="mt-1 text-lg font-black text-brand-ink">{{ $artworkUpload['title'] ?? 'Upload Custom Artwork' }}</h3>
                            <p class="mt-2 text-sm leading-7 text-slate-600">{{ $artworkUpload['description'] ?? 'Upload one or more artwork files for the production team.' }}</p>
                            @if(!empty($artworkUpload['accepted_types'] ?? []))
                                <p class="mt-3 text-xs font-black uppercase tracking-[.08em] text-slate-500">Accepted files: {{ collect($artworkUpload['accepted_types'])->map(fn ($type) => strtoupper($type))->implode(', ') }}</p>
                            @endif
                        </div>
                    @endif
                @else
                    <p class="text-sm text-slate-500">No customization or artwork guidelines have been added.</p>
                @endif
            </div>

            <div x-show="tab === 'fulfillment'">
                @if($fulfillmentHtml !== '')
                    <div class="product-rich-content">{!! $fulfillmentHtml !!}</div>
                @else
                    <p class="p-8 text-center text-sm text-slate-500">No fulfillment details have been added.</p>
                @endif
            </div>

            <div x-show="tab === 'faq'" class="space-y-3">@forelse($product['faqs'] as $faq)<details class="rounded-2xl border border-slate-200 bg-white p-4"><summary class="cursor-pointer list-none font-black text-brand-ink">{{ $faq['question'] }}</summary><p class="mt-3 text-sm leading-7 text-slate-600">{{ $faq['answer'] }}</p></details>@empty<p class="text-sm text-slate-500">No product-specific FAQs have been added.</p>@endforelse</div>
        </div>
    </div>
</section>
