@props(['product'])
<section class="section-padding bg-white" x-data="{ tab: 'description' }">
    <div class="site-container overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-card sm:rounded-[28px]">
        <div class="grid grid-cols-3 border-b border-slate-200 bg-slate-50">
            @foreach(['description'=>'Description','specifications'=>'Specifications','faq'=>'FAQ'] as $key => $label)
                <button type="button" @click="tab='{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-white text-brand-red shadow-[inset_0_-3px_0_#e91d33]' : 'text-slate-600'" class="min-h-12 min-w-0 border-r border-slate-200 px-2 py-3 text-[11px] font-black leading-tight last:border-r-0 sm:px-5 sm:py-4 sm:text-sm">{{ $label }}</button>
            @endforeach
        </div>
        <div class="p-4 sm:p-8">
            <div x-show="tab === 'description'" class="product-rich-content">{!! $product['description_html'] !!}</div>
            <div x-show="tab === 'specifications'">
                <table class="w-full table-fixed border-collapse text-sm"><tbody>@forelse($product['detail_information'] as $name => $value)<tr><th class="w-[42%] break-words border border-slate-200 bg-slate-50 p-3 text-left align-top font-black sm:w-1/3 sm:p-4">{{ $name }}</th><td class="break-words border border-slate-200 p-3 align-top text-slate-600 sm:p-4">{{ $value }}</td></tr>@empty<tr><td class="p-8 text-center text-slate-500">No specifications have been added.</td></tr>@endforelse</tbody></table>
            </div>
            <div x-show="tab === 'faq'" class="space-y-3">@forelse($product['faqs'] as $faq)<details class="rounded-2xl border border-slate-200 bg-white p-4"><summary class="cursor-pointer list-none font-black text-brand-ink">{{ $faq['question'] }}</summary><p class="mt-3 text-sm leading-7 text-slate-600">{{ $faq['answer'] }}</p></details>@empty<p class="text-sm text-slate-500">No product-specific FAQs have been added.</p>@endforelse</div>
        </div>
    </div>
</section>
