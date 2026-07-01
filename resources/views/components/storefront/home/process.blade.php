@props(['steps' => []])

<section id="process">
    <div class="container">
        <div class="section-head">
            <span class="small-red">How it works</span>
            <h2>Simple Ordering Process</h2>
            <p>A clear process from product selection to delivery.</p>
        </div>
        <div class="process">
            @foreach($steps as $step)
                <article class="process-step">
                    <div class="process-icon">{{ $loop->iteration }}</div>
                    <h3>{{ $step['title'] }}</h3>
                    <p>{{ $step['description'] }}</p>
                </article>
            @endforeach
        </div>
        <p class="home-center-action"><a class="btn btn-red" href="#products">Start Your Order</a></p>
    </div>
</section>
