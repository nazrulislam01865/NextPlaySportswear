@props(['item', 'compact' => false])
<div class="grid min-w-0 grid-cols-[64px_minmax(0,1fr)] gap-3 rounded-2xl border border-slate-200 bg-white p-3 sm:grid-cols-[80px_minmax(0,1fr)] sm:gap-4 sm:p-4">
    <img src="{{ \App\Support\PublicMedia::url(null, $item->image_url, '/images/product-placeholder.svg') }}" alt="{{ $item->product_name }}" class="h-16 w-16 rounded-xl object-cover sm:h-20 sm:w-20" loading="lazy">
    <div class="min-w-0 flex-1">
        <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-start">
            <div>
                <h4 class="font-black text-brand-ink">{{ $item->product_name }}</h4>
                <p class="mt-1 text-xs font-bold text-slate-500">{{ $item->sku ?: 'Custom product' }} · Quantity {{ $item->quantity }}</p>
            </div>
            <strong class="text-sm font-black text-brand-ink">${{ number_format((float)$item->line_total, 2) }}</strong>
        </div>
        @unless($compact)
            @php($customization = (array) $item->customization)
            <div class="mt-3 grid gap-1 text-xs leading-5 text-slate-600">
                @if(!empty($customization['design_option']))<p><b>Design:</b> {{ $customization['design_option'] }}</p>@endif
                @if(!empty($customization['size_summary']))<p><b>Sizes:</b> {{ $customization['size_summary'] }}</p>@endif
                @if(!empty($customization['notes']))<p><b>Notes:</b> {{ $customization['notes'] }}</p>@endif
            </div>
        @endunless
    </div>
</div>
