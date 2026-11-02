@php
    $title = data_get($content, 'hero.title', 'Terms and Conditions');
    $description = data_get($content, 'hero.description', 'These terms explain the ordering process and your responsibilities when shopping with NextPlay Sportswear.');
    $termsSections = data_get($content, 'sections', []);
@endphp

<x-layouts.storefront :seo="$seo">
<div class="bg-white py-8 sm:py-12 lg:py-14">
        <div class="site-container">
            <div class="np-legal-document mx-auto">
                {{-- Breadcrumb --}}
                <nav class="nps-breadcrumb" aria-label="Breadcrumb">
                    <ol>
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li><span aria-current="page">{{ $title }}</span></li>
                    </ol>
                </nav>

                {{-- Page Header --}}
                <header>
                    <h1>{{ $title }}</h1>
                    <p class="np-legal-intro">
                        {{ $description }}
                    </p>
                </header>

                {{-- Managed terms: preserve established document structure, links and typography. --}}
                <div class="np-legal-sections">
                    @foreach($termsSections as $index => $section)
                        <section id="terms-section-{{ $index }}" aria-labelledby="terms-heading-{{ $index }}">
                            <h2 id="terms-heading-{{ $index }}">{{ data_get($section, 'title') }}</h2>
                            @if($index === '07')
                                <p>{!! str_replace('Returns and Refunds Policy', '<a href="'.e(route('returns')).'">Returns and Refunds Policy</a>', e((string) data_get($section, 'content', ''))) !!}</p>
                            @elseif($index === '10')
                                <p>{!! str_replace('Privacy Policy', '<a href="'.e(route('privacy')).'">Privacy Policy</a>', e((string) data_get($section, 'content', ''))) !!}</p>
                            @elseif($index === '11')
                                <p>{!! str_replace('Contact Us', '<a href="'.e(route('contact')).'">Contact Us</a>', e((string) data_get($section, 'content', ''))) !!}</p>
                            @else
                                <p>{!! nl2br(e((string) data_get($section, 'content', ''))) !!}</p>
                            @endif
                        </section>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-layouts.storefront>
