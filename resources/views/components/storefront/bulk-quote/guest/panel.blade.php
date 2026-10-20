@props(['title' => '', 'subtitle' => '', 'tone' => '', 'icon' => ''])
<section {{ $attributes->class(['np-gq-panel', 'np-gq-panel--'.$tone => $tone !== '']) }}>
    @if($title !== '')
        <div class="np-gq-panel-heading">
            @if($icon !== '')
                <span class="np-gq-section-icon"><x-storefront.bulk-quote.guest.icon :name="$icon" size="22" /></span>
            @endif
            <div>
                <h2>{{ $title }}</h2>
                @if($subtitle !== '')
                    <p>{{ $subtitle }}</p>
                @endif
            </div>
        </div>
    @endif
    {{ $slot }}
</section>
