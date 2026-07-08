@props(['faqs' => [], 'section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $items = collect(data_get($section, 'items', []))->map(fn ($item) => [
        'question' => data_get($item, 'title'),
        'answer' => data_get($item, 'description'),
    ])->filter(fn ($item) => filled($item['question']) && filled($item['answer']))->values();
    $faqs = $items->isNotEmpty() ? $items : collect($faqs);
@endphp

<section class="section-alt">
    <div class="container">
        <div class="section-head">
            <span class="small-red">{{ $text('eyebrow', 'Help center') }}</span>
            <h2>{{ $text('title', 'Common Questions') }}</h2>
            @if(filled($text('description')))<p>{{ $text('description') }}</p>@endif
        </div>
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
</section>
