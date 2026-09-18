@props(['title', 'description' => null])
<section {{ $attributes->class('np-home-admin__panel rounded-2xl p-5 sm:p-6') }}>
    <div class="mb-5">
        <h2 class="np-home-admin__panel-title text-lg font-bold">{{ $title }}</h2>
        @if($description)<p class="np-home-admin__muted mt-1 text-sm">{{ $description }}</p>@endif
    </div>
    {{ $slot }}
</section>
