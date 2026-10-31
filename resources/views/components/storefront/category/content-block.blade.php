@props(['block'])
@php
    $type = $block['type'] ?? 'rich_text';
    $items = $block['items'] ?? [];
@endphp

@if($type === 'rich_text')
    <section class="np-category-content-block site-container py-10"><div class="np-category-richtext prose prose-slate max-w-none rounded-3xl border border-slate-200 bg-white p-6 shadow-card">@if($block['heading'])<h2 class="np-category-content-heading">{{ $block['heading'] }}</h2>@endif {!! $block['content_html'] !!}</div></section>
@elseif(in_array($type, ['image_text','promo_banner','cta'], true))
    <section class="np-category-content-block site-container py-8"><div class="grid overflow-hidden rounded-3xl bg-brand-dark text-white shadow-card {{ $block['image'] ? 'lg:grid-cols-2' : '' }}">@if($block['image'])<img src="{{ $block['image'] }}" alt="{{ $block['image_alt'] }}" class="h-full min-h-64 w-full object-cover" loading="lazy">@endif<div class="p-7 sm:p-10"><p class="np-category-eyebrow np-category-eyebrow--inverse">{{ $block['subheading'] }}</p><h2 class="np-category-content-heading np-category-inverse-title mt-2 font-display text-4xl font-bold uppercase">{{ $block['heading'] }}</h2><div class="np-category-inverse-copy mt-4">{!! $block['content_html'] !!}</div>@if($block['button_label'] && $block['button_url'])<a class="btn btn-primary np-category-action mt-6" href="{{ $block['button_url'] }}">{{ $block['button_label'] }}</a>@endif</div></div></section>
@elseif(in_array($type, ['featured_products','selected_products'], true) && $items !== [])
    <section class="np-category-content-block site-container np-products-catalog-container py-10"><div><p class="np-category-eyebrow">{{ $block['subheading'] }}</p><h2 class="np-category-content-heading mt-2 font-display text-4xl font-bold uppercase text-brand-ink">{{ $block['heading'] ?: 'Featured products' }}</h2></div><div class="np-product-listing-grid np-product-listing-grid--three mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">@foreach($items as $product)<x-storefront.product-card :product="$product" />@endforeach</div></section>
@elseif(in_array($type, ['child_categories','related_categories'], true) && $items !== [])
    <section class="np-category-content-block site-container py-10">
        <h2 class="np-category-content-heading font-display text-4xl font-bold uppercase text-brand-ink">{{ $block['heading'] ?: ($type === 'child_categories' ? 'Shop by category' : 'Related categories') }}</h2>
        <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($items as $item)
                <a href="{{ $item['url'] }}" class="np-category-child-card np-square-card group overflow-hidden border border-slate-200 bg-white shadow-card">
                    <div class="np-category-square-media relative overflow-hidden" data-card-image-loader>
                        <img src="{{ $item['image'] }}" alt="{{ $item['alt'] }}" class="np-category-square-image transition group-hover:scale-105" data-card-image loading="lazy" width="800" height="800">
                    </div>
                    <div class="p-4">
                        <strong class="np-category-child-title">{{ $item['title'] }}</strong>
                        @if(!empty($item['description']))<p class="np-category-child-description mt-1">{{ $item['description'] }}</p>@endif
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@elseif($type === 'highlights' && $items !== [])
    <section class="np-category-content-block site-container py-10"><h2 class="np-category-content-heading font-display text-4xl font-bold uppercase text-brand-ink">{{ $block['heading'] ?: 'Category highlights' }}</h2><div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@foreach($items as $item)<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"><span class="grid h-8 w-8 place-items-center rounded-full bg-emerald-50 font-black text-emerald-700">✓</span><strong class="np-category-highlight-title mt-3 block">{{ $item['label'] }}</strong></div>@endforeach</div></section>
@elseif($type === 'video' && !empty($block['video_embed_url']))
    <section class="np-category-content-block site-container py-10"><div class="mb-5"><p class="np-category-eyebrow">{{ $block['subheading'] }}</p><h2 class="np-category-content-heading mt-2 font-display text-4xl font-bold uppercase text-brand-ink">{{ $block['heading'] ?: 'Watch the category guide' }}</h2></div><div class="aspect-video overflow-hidden rounded-3xl bg-slate-950 shadow-card"><iframe src="{{ $block['video_embed_url'] }}" title="{{ $block['heading'] ?: 'Category video' }}" class="h-full w-full" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div></section>
@elseif($type === 'faq' && $items !== [])
    <section class="np-category-content-block site-container py-10"><h2 class="np-category-content-heading font-display text-4xl font-bold uppercase text-brand-ink">{{ $block['heading'] ?: 'Frequently asked questions' }}</h2><div class="mt-6 grid gap-3">@foreach($items as $item)<details class="rounded-2xl border border-slate-200 bg-white p-5"><summary class="np-category-faq-question cursor-pointer">{{ $item['question'] }}</summary><div class="np-category-prose prose mt-4 max-w-none">{!! $item['answer_html'] !!}</div></details>@endforeach</div></section>
@elseif(in_array($type, ['logo_list','trust_badges'], true) && $items !== [])
    <section class="np-category-content-block site-container py-10"><h2 class="np-category-content-heading text-center font-display text-4xl font-bold uppercase text-brand-ink">{{ $block['heading'] }}</h2><div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@foreach($items as $item)<div class="rounded-2xl border border-slate-200 bg-white p-5 text-center shadow-card">@if($item['image'])<img src="{{ $item['image'] }}" alt="{{ $item['label'] }}" class="mx-auto h-14 max-w-32 object-contain" loading="lazy">@endif<strong class="np-category-highlight-title mt-3 block">{{ $item['label'] }}</strong>@if($item['description'])<p class="np-category-highlight-description mt-2">{{ $item['description'] }}</p>@endif</div>@endforeach</div></section>
@endif
