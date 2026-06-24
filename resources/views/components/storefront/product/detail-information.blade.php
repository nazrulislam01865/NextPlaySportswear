@props(['product' => []])

@php
    $detailInformation = collect($product['detail_information'] ?? [])
        ->filter(fn ($value, $label) => filled($label) && filled($value))
        ->take(8);

    if ($detailInformation->isEmpty()) {
        $detailInformation = collect([
            'Product Type' => $product['product_type'] ?? null,
            'Brand' => $product['brand'] ?? null,
            'Minimum Order' => isset($product['minimum_quantity'])
                ? number_format((int) $product['minimum_quantity']).' '.((int) $product['minimum_quantity'] === 1 ? 'Piece' : 'Pieces')
                : null,
        ])->filter();
    }

    $tags = collect($product['tags'] ?? [])->filter()->values();
    $categories = collect($product['categories'] ?? [])->filter(fn ($category) => filled($category['name'] ?? null));
@endphp

<div class="mt-7">
    @if($detailInformation->isNotEmpty())
        <div class="overflow-hidden border-y border-slate-200">
            <table class="w-full table-fixed border-collapse text-left">
                <thead>
                    <tr class="border-b border-slate-200 text-sm font-black uppercase tracking-[.04em] text-slate-700">
                        <th class="w-[34%] px-0 py-3 pr-4 sm:w-[31%]">Detail</th>
                        <th class="px-0 py-3">Information</th>
                    </tr>
                </thead>
                <tbody class="text-[15px] leading-6 text-slate-600 sm:text-base">
                    @foreach($detailInformation as $label => $value)
                        <tr class="border-b border-slate-200 last:border-b-0">
                            <th class="break-words px-0 py-3 pr-4 text-left align-top font-medium text-slate-600">{{ $label }}</th>
                            <td class="break-words px-0 py-3 align-top font-medium text-slate-600">{{ $value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="mt-5 divide-y divide-dotted divide-slate-300 border-y border-dotted border-slate-300 text-sm leading-6 text-slate-700 sm:text-[15px]">
        <div class="py-3">
            <span class="font-semibold text-slate-800">SKU:</span>
            <span>{{ $product['sku'] ?? 'N/A' }}</span>
        </div>

        <div class="py-3">
            <span class="font-semibold text-slate-800">Category:</span>
            @if($categories->isNotEmpty())
                @foreach($categories as $category)
                    @if(! $loop->first)<span class="text-slate-400">,</span>@endif
                    @if(filled($category['slug'] ?? null))
                        <a href="{{ route('categories.show', $category['slug']) }}" class="font-medium text-blue-800 hover:text-brand-red hover:underline">
                            {{ $category['name'] }}
                        </a>
                    @else
                        <span class="font-medium text-blue-800">{{ $category['name'] }}</span>
                    @endif
                @endforeach
            @elseif(filled($product['category'] ?? null) && filled($product['category_slug'] ?? null))
                <a href="{{ route('categories.show', $product['category_slug']) }}" class="font-medium text-blue-800 hover:text-brand-red hover:underline">
                    {{ $product['category'] }}
                </a>
            @else
                <span>{{ $product['category'] ?? 'Custom Sportswear' }}</span>
            @endif
        </div>

        @if($tags->isNotEmpty())
            <div class="py-3">
                <span class="font-semibold text-slate-800">Tags:</span>
                <span class="text-blue-800">
                    @foreach($tags as $tag)
                        <span class="font-medium">{{ $tag }}</span>@if(! $loop->last)<span class="text-slate-400">,</span>@endif
                    @endforeach
                </span>
            </div>
        @endif

        @if(filled($product['brand'] ?? null))
            <div class="py-3">
                <span class="font-semibold text-slate-800">Brand:</span>
                <span>{{ $product['brand'] }}</span>
            </div>
        @endif
    </div>
</div>
