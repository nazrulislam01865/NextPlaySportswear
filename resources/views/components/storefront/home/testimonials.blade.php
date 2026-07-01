@php
    $testimonials = [
        ['name' => 'Jason Miller', 'meta' => 'River Valley Baseball Club · Ohio, USA', 'quote' => 'The jerseys came out clean and the ordering process was simple. We shared our team logo and size list, and the team helped us prepare the final order.'],
        ['name' => 'Emily Carter', 'meta' => 'Community Run Event · Texas, USA', 'quote' => 'We needed shirts and bags for a weekend event. The quote was clear, and they asked the right questions before moving ahead.'],
        ['name' => 'Marcus Reed', 'meta' => 'Northside High Boosters · Georgia, USA', 'quote' => 'Good option for our school spirit wear. The hoodie colors matched what we requested, and the design proof helped a lot.'],
        ['name' => 'Olivia Grant', 'meta' => 'Grant Family Dental · Arizona, USA', 'quote' => 'Ordering caps for our business team was easy. We had a few logo questions, and they helped us clean that up before production.'],
        ['name' => 'Daniel Ruiz', 'meta' => 'South Bay FC · California, USA', 'quote' => 'The soccer kits looked sharp. Not overcomplicated. We sent names, numbers, and sizes, then reviewed the mockup.'],
        ['name' => 'Lauren Brooks', 'meta' => 'Lakeview Youth League · Florida, USA', 'quote' => 'We used them for a league order. The bulk quote made more sense than ordering every piece one by one.'],
    ];
@endphp

<section>
    <div class="container">
        <div class="section-head">
            <span class="small-red">Customer words</span>
            <h2>What Teams and Customers Say</h2>
        </div>
        <div class="testimonial-grid">
            @foreach($testimonials as $testimonial)
                <article class="testimonial">
                    <div class="stars-line" aria-label="5 out of 5 stars">★★★★★</div>
                    <p>“{{ $testimonial['quote'] }}”</p>
                    <div class="person"><strong>{{ $testimonial['name'] }}</strong><span>{{ $testimonial['meta'] }}</span></div>
                </article>
            @endforeach
        </div>
        <p class="home-center-action"><a class="btn btn-light" href="{{ route('products.index') }}">View Product</a></p>
    </div>
</section>
