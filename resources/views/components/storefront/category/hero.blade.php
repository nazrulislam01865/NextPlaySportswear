@props([
    'category',
])

<section class="relative isolate overflow-hidden bg-brand-dark text-white">
    <img
        src="{{ $category['image'] }}"
        alt="{{ $category['alt'] }}"
        class="absolute inset-0 -z-20 h-full w-full object-cover opacity-35"
        fetchpriority="high"
    >
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-brand-dark via-brand-navy/95 to-brand-navy/45"></div>
    <div class="absolute inset-y-0 right-0 -z-10 w-1/2 bg-[radial-gradient(circle_at_center,rgba(255,255,255,.16),transparent_64%)]"></div>

    <div class="site-container py-12 sm:py-16 lg:py-20">
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-xs font-bold text-blue-100">
            <a href="{{ route('home') }}" class="transition hover:text-white">Home</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('categories.index') }}" class="transition hover:text-white">Categories</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page" class="text-white">{{ $category['title'] }}</span>
        </nav>

        <div class="mt-10 max-w-3xl">
            <p class="text-xs font-black uppercase tracking-[.22em] text-red-200">{{ $category['eyebrow'] }}</p>
            <h1 class="mt-3 font-display text-4xl font-bold uppercase leading-tight tracking-tight sm:text-6xl lg:text-7xl">
                {{ $category['title'] }}
            </h1>
            <p class="mt-5 max-w-2xl text-base leading-7 text-blue-50 sm:text-lg">
                {{ $category['description'] }}
            </p>

            <ul class="mt-7 grid max-w-2xl gap-3 text-sm font-bold text-white sm:grid-cols-3" aria-label="Category highlights">
                @foreach ($category['highlights'] as $highlight)
                    <li class="flex items-start gap-2 rounded-xl border border-white/15 bg-white/10 px-3 py-3 backdrop-blur-sm">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-200" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.31a1 1 0 0 1-1.42.002l-3.75-3.75a1 1 0 1 1 1.414-1.414l3.04 3.04 6.544-6.596a1 1 0 0 1 1.416-.006Z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ $highlight }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="#category-products" class="btn btn-red">Shop {{ $category['short_title'] }}</a>
                <a href="{{ route('quote.request') }}" class="btn border border-white/30 bg-white/10 text-white backdrop-blur-sm hover:bg-white hover:text-brand-ink">Request Team Quote</a>
            </div>
        </div>
    </div>
</section>
