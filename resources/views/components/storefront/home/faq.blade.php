@props(['faqs' => [], 'section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $items = collect(data_get($section, 'items', []))->map(fn ($item) => [
        'question' => data_get($item, 'title'),
        'answer' => data_get($item, 'description'),
    ])->filter(fn ($item) => filled($item['question']) && filled($item['answer']))->values();
    $faqs = $items->isNotEmpty() ? $items : collect($faqs);
    $settings = is_array(data_get($section, 'settings')) ? data_get($section, 'settings') : [];
    $quickHelpCards = collect($settings['quick_help_cards'] ?? [])
        ->filter(fn ($card) => is_array($card) && ($card['enabled'] ?? true) && filled($card['title'] ?? null) && filled($card['url'] ?? null))
        ->values();
    $showQuickHelp = ($settings['show_quick_help'] ?? true) && $quickHelpCards->isNotEmpty();
@endphp

<section class="section-alt np-home-faq-section">
    <div class="container">
        <div class="np-home-faq-layout{{ $showQuickHelp ? '' : ' np-home-faq-layout--single' }}">
            <div class="np-home-faq-column">
                <x-storefront.home.section-heading
                    class="section-head"
                    align="left"
                    :title="$text('title', 'Common Questions')"
                    :description="$text('description')"
                />

                <div class="faq" id="faq" data-home-faq>
                    @foreach($faqs as $faq)
                        @php($answerId = 'home-faq-answer-'.$loop->iteration)
                        <div class="faq-item">
                            <button class="faq-q" type="button" aria-expanded="false" aria-controls="{{ $answerId }}">
                                <span>{{ $faq['question'] }}</span>
                                <span aria-hidden="true">+</span>
                            </button>
                            <div class="faq-a" id="{{ $answerId }}">{{ $faq['answer'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($showQuickHelp)
                <x-storefront.home.quick-help
                    :title="(string) ($settings['quick_help_title'] ?? 'Quick Help')"
                    :description="(string) ($settings['quick_help_description'] ?? 'Find what you need.')"
                    :cards="$quickHelpCards"
                />
            @endif
        </div>
    </div>
</section>
