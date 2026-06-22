@props(['product'])
<section class="section-padding bg-white" x-data="{ tab: 'description' }">
    <div class="site-container overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-card">
        <div class="flex overflow-x-auto border-b border-slate-200 bg-slate-50">
            @foreach(['description'=>'Description','specifications'=>'Specifications','faq'=>'FAQ'] as $key => $label)<button type="button" @click="tab='{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-white text-brand-red shadow-[inset_0_-3px_0_#e91d33]' : 'text-slate-600'" class="min-w-[160px] border-r border-slate-200 px-5 py-4 text-sm font-black">{{ $label }}</button>@endforeach
        </div>
        <div class="p-6 sm:p-8">
            <div x-show="tab === 'description'" class="product-rich-content">{!! $product['description_html'] !!}</div>
            <div x-show="tab === 'specifications'" class="overflow-x-auto"><table class="min-w-full border-collapse text-sm"><tbody>@forelse($product['detail_information'] as $name => $value)<tr><th class="w-1/3 border border-slate-200 bg-slate-50 p-4 text-left font-black">{{ $name }}</th><td class="border border-slate-200 p-4 text-slate-600">{{ $value }}</td></tr>@empty<tr><td class="p-8 text-center text-slate-500">No specifications have been added.</td></tr>@endforelse</tbody></table></div>
            <div x-show="tab === 'faq'" class="space-y-3">@forelse($product['faqs'] as $faq)<details class="rounded-2xl border border-slate-200 bg-white p-4"><summary class="cursor-pointer list-none font-black text-brand-ink">{{ $faq['question'] }}</summary><p class="mt-3 text-sm leading-7 text-slate-600">{{ $faq['answer'] }}</p></details>@empty<p class="text-sm text-slate-500">No product-specific FAQs have been added.</p>@endforelse</div>
        </div>
    </div>
</section>
