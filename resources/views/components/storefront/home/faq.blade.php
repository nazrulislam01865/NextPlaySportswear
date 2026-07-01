@props(['faqs' => []])

<section class="section-alt">
    <div class="container">
        <div class="section-head">
            <span class="small-red">Help center</span>
            <h2>Common Questions</h2>
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
