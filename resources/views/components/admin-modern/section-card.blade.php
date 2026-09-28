@props([
    'number',
    'title',
    'description' => null,
])

<section {{ $attributes->class('admin-modern-section-card') }}>
    <header class="admin-modern-section-heading">
        <h2><span>{{ $number }}.</span> {{ $title }}</h2>
        @if($description)
            <p>{{ $description }}</p>
        @endif
    </header>
    <div class="admin-modern-section-body">
        {{ $slot }}
    </div>
</section>
