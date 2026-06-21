@props([
    'product' => [],
])

@php
    $detailInformation = $product['detail_information'] ?? [];
    $tags = $product['tags'] ?? [];
@endphp

<section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
    <div class="flex flex-col gap-2 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Product information</p>
            <h2 class="mt-1 text-xl font-black text-brand-ink">Detail Information</h2>
        </div>
        <span class="w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-brand-blue">SKU: {{ $product['sku'] ?? 'N/A' }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="whitespace-nowrap px-5 py-3 font-black">Detail</th>
                    <th class="whitespace-nowrap px-5 py-3 font-black">Information</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @foreach ($detailInformation as $label => $value)
                    <tr>
                        <td class="whitespace-nowrap px-5 py-3 font-black text-brand-ink">{{ $label }}</td>
                        <td class="px-5 py-3 font-semibold text-slate-700">{{ $value }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="grid gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4 text-sm text-slate-700">
        <p><span class="font-black text-brand-ink">SKU:</span> {{ $product['sku'] ?? 'N/A' }}</p>
        <p><span class="font-black text-brand-ink">Category:</span> {{ $product['category'] ?? 'Custom Sportswear' }}</p>
        @if (! empty($tags))
            <p class="leading-6"><span class="font-black text-brand-ink">Tags:</span> {{ implode(', ', $tags) }}</p>
        @endif
        <p><span class="font-black text-brand-ink">Brand:</span> {{ $product['brand'] ?? ($product['sport'] ?? config('storefront.name')) }}</p>
    </div>
</section>
