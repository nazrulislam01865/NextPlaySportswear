@props([
    'title',
    'description',
    'primaryLabel' => 'Shop Now',
    'primaryHref' => null,
    'secondaryLabel' => 'Request a Quote',
    'secondaryHref' => null,
])

<section class="bg-brand-navy py-14 text-center text-white sm:py-16">
    <div class="site-container">
        <h2 class="font-display text-3xl font-bold uppercase sm:text-4xl">{{ $title }}</h2>
        <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-white/75 sm:text-base">{{ $description }}</p>
        <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
            <a href="{{ $primaryHref ?? route('products.index') }}" class="btn btn-red">{{ $primaryLabel }}</a>
            <a href="{{ $secondaryHref ?? route('quote.request') }}" class="btn btn-white">{{ $secondaryLabel }}</a>
        </div>
    </div>
</section>
