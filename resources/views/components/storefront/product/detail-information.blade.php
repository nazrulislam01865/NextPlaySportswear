@props(['product' => []])

@php
    $detailInformationHtml = trim((string) ($product['detail_information_html'] ?? ''));
    $detailInformation = collect($product['detail_information'] ?? [])
        ->filter(fn ($value, $label) => filled($label) && filled($value))
        ->take(30);

    if ($detailInformation->isEmpty()) {
        $detailInformation = collect([
            'Product Type' => $product['product_type'] ?? null,
            'Minimum Order' => isset($product['minimum_quantity'])
                ? number_format((int) $product['minimum_quantity']).' '.((int) $product['minimum_quantity'] === 1 ? 'Piece' : 'Pieces')
                : null,
        ])->filter();
    }

    $tags = collect($product['tags'] ?? [])->filter()->values();
    $categories = collect($product['categories'] ?? [])->filter(fn ($category) => filled($category['name'] ?? null));
@endphp

<div class="np-detail-information mt-6">
    @if($detailInformationHtml !== '')
        <div class="product-rich-content overflow-hidden border-y border-slate-200 py-4">{!! $detailInformationHtml !!}</div>
    @elseif($detailInformation->isNotEmpty())
        <div class="overflow-hidden border-y border-slate-200">
            <table class="w-full table-fixed border-collapse text-left">
                <thead>
                    <tr class="border-b border-slate-200 text-[11px] font-black uppercase tracking-[.04em] text-slate-700 sm:text-xs">
                        <th class="w-[34%] px-0 py-2.5 pr-4 sm:w-[31%]">Detail</th>
                        <th class="px-0 py-2.5">Information</th>
                    </tr>
                </thead>
                <tbody class="text-[13px] leading-5 text-slate-600 sm:text-sm">
                    @foreach($detailInformation as $label => $value)
                        <tr class="border-b border-slate-200 last:border-b-0">
                            <th class="break-words px-0 py-2.5 pr-4 text-left align-top font-medium text-slate-600">{{ $label }}</th>
                            <td class="break-words px-0 py-2.5 align-top font-medium text-slate-600">{{ $value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="np-product-meta mt-4 divide-y divide-dotted divide-slate-300 border-y border-dotted border-slate-300 text-slate-700">
        <div class="py-2.5">
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
            <div class="py-2.5">
                <span class="font-semibold text-slate-800">Tags:</span>
                <span class="text-blue-800">
                    @foreach($tags as $tag)
                        <a href="{{ route('products.index', ['tag' => $tag]) }}" class="font-medium text-blue-800 hover:text-brand-red hover:underline">{{ $tag }}</a>@if(! $loop->last)<span class="text-slate-400">,</span>@endif
                    @endforeach
                </span>
            </div>
        @endif

    </div>
</div>
