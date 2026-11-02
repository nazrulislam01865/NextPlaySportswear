@php
    $privacyTitle = (string) data_get($content, 'hero.title', 'Privacy Policy');
    $lastUpdatedLabel = (string) data_get($content, 'hero.updated_label', 'Last updated:');
    $lastUpdated = (string) data_get($content, 'hero.subtitle', '22 June 2026');
    $description = (string) data_get($content, 'hero.description', '');
    $homeLabel = (string) data_get($content, 'navigation.home_label', 'Home');
    $breadcrumbLabel = trim((string) data_get($content, 'navigation.breadcrumb_label', '')) ?: $privacyTitle;
    $backToTopLabel = (string) data_get($content, 'navigation.back_to_top_label', '↑ Back to top');
@endphp
<x-layouts.storefront :seo="$seo">
    {{-- Match the shared Terms & Conditions outer width and breadcrumb layout. --}}
    <div class="bg-white py-8 sm:py-12 lg:py-14" id="privacy-document-top">
        <div class="site-container">
            <div class="np-legal-document np-privacy-document">
                <nav class="nps-breadcrumb" aria-label="Breadcrumb">
                    <ol>
                        <li><a href="{{ route('home') }}">{{ $homeLabel }}</a></li>
                        <li><span aria-current="page">{{ $breadcrumbLabel }}</span></li>
                    </ol>
                </nav>

                <header class="np-privacy-document__header">
                    <h1>{{ $privacyTitle }}</h1>
                    <p class="np-privacy-document__updated">{{ $lastUpdatedLabel }} {{ $lastUpdated }}</p>
                    <p class="np-legal-intro np-privacy-document__intro">{{ $description }}</p>
                </header>

                <div class="np-privacy-document__sections">
                    @foreach($privacySections as $section)
                        <section class="np-privacy-document__section" id="{{ $section['id'] }}" aria-labelledby="privacy-heading-{{ $section['number'] }}">
                            <h2 id="privacy-heading-{{ $section['number'] }}">{{ $section['number'] }}. {{ $section['title'] }}</h2>
                            @foreach($section['paragraphs'] as $paragraph)
                                <p>{!! nl2br(str_replace('Contact Us', '<a href="'.e(route('contact')).'">Contact Us</a>', e($paragraph))) !!}</p>
                            @endforeach
                        </section>
                    @endforeach
                </div>

                <a class="np-back-to-top np-privacy-document__back" href="#privacy-document-top">{{ $backToTopLabel }}</a>
            </div>
        </div>
    </div>
</x-layouts.storefront>
