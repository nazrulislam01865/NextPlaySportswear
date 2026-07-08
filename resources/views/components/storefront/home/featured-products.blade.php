@props(['products' => [], 'section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
@endphp

<section id="products" class="section-alt">
    <div class="container">
        <div class="section-head">
            <span class="small-red">{{ $text('eyebrow', 'Shop online') }}</span>
            <h2>{{ $text('title', 'Featured Products') }}</h2>
            @if(filled($text('description')))<p>{{ $text('description', 'Products marked as featured by the admin appear here automatically.') }}</p>@endif
        </div>
        <div class="grid-4">
            @forelse($products as $product)
                <x-storefront.product-card :product="$product" />
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 sm:col-span-2 lg:col-span-4">
                    No active featured products are available yet.
                </div>
            @endforelse
        </div>
    </div>
</section>
