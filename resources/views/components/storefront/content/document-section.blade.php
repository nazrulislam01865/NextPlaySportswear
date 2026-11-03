@props(['id', 'number', 'title'])
<section id="{{ $id }}" class="np-returns-section np-payment-section" aria-labelledby="{{ $id }}-heading">
    <span class="np-section-num" aria-hidden="true">{{ $number }}</span>
    <div class="np-section-body">
        <h2 id="{{ $id }}-heading" class="np-section-heading">{{ $title }}</h2>
        {{ $slot }}
    </div>
</section>
