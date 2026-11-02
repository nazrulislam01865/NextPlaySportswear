@php
    $siteName = config('storefront.name', 'NextPlay Sportswear');
    $title = data_get($content, 'hero.title', $title ?? 'Privacy Policy');
    $updated = data_get($content, 'hero.subtitle', $updated ?? '22 June 2026');
    $description = data_get($content, 'hero.description', $description ?? 'This policy explains how NextPlay Sportswear handles your personal information when you use our website and services.');
    $sections = data_get($content, 'sections', []);
@endphp

<x-layouts.storefront :seo="$seo ?? [
    'title' => $title . ' | ' . $siteName,
    'description' => $description,
]">
    <style>
        .np-legal-document {
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            color: var(--nps-text, #26364b);
            margin-inline: auto;
        }
        .np-legal-document .nps-breadcrumb {
            font-size: 0.8125rem;
            line-height: 1.5;
            color: var(--nps-muted, #52637a);
            margin-bottom: 1.5rem;
        }
        .np-legal-document .nps-breadcrumb ol {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
            padding: 0;
            margin: 0;
        }
        .np-legal-document .nps-breadcrumb li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .np-legal-document .nps-breadcrumb li + li::before {
            content: "/";
            color: #8a99ad;
        }
        .np-legal-document .nps-breadcrumb a {
            color: var(--nps-muted, #52637a);
            text-decoration: none;
            min-height: auto;
            display: inline-flex;
            align-items: center;
            transition: color 0.15s ease;
        }
        .np-legal-document .nps-breadcrumb a:hover {
            color: var(--nps-navy, #051e44);
            text-decoration: underline;
        }
        .np-legal-document .nps-breadcrumb [aria-current] {
            color: var(--nps-muted, #52637a);
            font-weight: 400;
        }
        .np-legal-document h1 {
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-size: 2.375rem;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -0.025em;
            color: var(--nps-navy, #051e44);
            margin: 0 0 0.5rem 0;
        }
        .np-legal-document .np-legal-subtitle {
            font-size: 0.875rem;
            color: var(--nps-muted, #52637a);
            margin: 0 0 1.25rem 0;
        }
        .np-legal-document .np-legal-intro {
            font-size: 1.0625rem;
            line-height: 1.6;
            color: var(--nps-text, #26364b);
            margin: 0 0 2.25rem 0;
        }
        .np-legal-document section {
            margin-bottom: 2.25rem;
        }
        .np-legal-document h2 {
            font-family: var(--nps-font, "Expose", system-ui, sans-serif);
            font-size: 1.375rem;
            font-weight: 700;
            line-height: 1.35;
            letter-spacing: -0.015em;
            color: var(--nps-navy, #051e44);
            margin: 0 0 0.625rem 0;
        }
        .np-legal-document p {
            font-size: 1rem;
            line-height: 1.65;
            color: var(--nps-text, #26364b);
            margin: 0 0 0.75rem 0;
        }
        .np-legal-document p:last-child {
            margin-bottom: 0;
        }
        .np-legal-document a:not(.nps-breadcrumb a) {
            color: var(--nps-link, #a43e20);
            text-decoration: underline;
            text-underline-offset: 0.2em;
            transition: color 0.15s ease;
        }
        .np-legal-document a:not(.nps-breadcrumb a):hover {
            color: var(--nps-navy, #051e44);
        }
        .np-legal-document .np-back-to-top {
            display: inline-flex;
            align-items: center;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--nps-link, #a43e20);
            text-decoration: underline;
            text-underline-offset: 0.2em;
            margin-top: 0.75rem;
            transition: color 0.15s ease;
        }
        .np-legal-document .np-back-to-top:hover {
            color: var(--nps-navy, #051e44);
        }
        @media (max-width: 640px) {
            .np-legal-document h1 {
                font-size: 1.75rem;
            }
            .np-legal-document h2 {
                font-size: 1.25rem;
            }
            .np-legal-document .np-legal-intro {
                font-size: 1rem;
            }
            .np-legal-document section {
                margin-bottom: 1.75rem;
            }
        }
    </style>

    <div class="bg-white py-8 sm:py-12 lg:py-14">
        <div class="site-container">
            <div class="np-legal-document max-w-3xl mx-auto">
                {{-- Breadcrumb --}}
                <nav class="nps-breadcrumb" aria-label="Breadcrumb">
                    <ol>
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li><span aria-current="page">Privacy Policy</span></li>
                    </ol>
                </nav>

                {{-- Page Header --}}
                <header>
                    <h1>{{ $title }}</h1>
                    <p class="np-legal-subtitle">
                        {{ \Illuminate\Support\Str::startsWith($updated, 'Last updated:') ? $updated : 'Last updated: ' . $updated }}
                    </p>
                    <p class="np-legal-intro">
                        {{ $description }}
                    </p>
                </header>

                {{-- Policy Sections --}}
                <div class="np-legal-sections">
                    @forelse($sections as $secKey => $sec)
                        <section aria-labelledby="section-{{ $secKey }}">
                            <h2 id="section-{{ $secKey }}">{{ data_get($sec, 'title') }}</h2>
                            @foreach(explode("\n\n", data_get($sec, 'content', '')) as $para)
                                @if(trim($para) !== '')
                                    @php
                                        $text = e(trim($para));
                                        $text = str_replace(
                                            ['Contact Us', 'Cookie Policy', 'Privacy Policy'],
                                            [
                                                '<a href="' . route('contact') . '">Contact Us</a>',
                                                '<a href="' . route('cookies') . '">Cookie Policy</a>',
                                                '<a href="' . route('privacy') . '">Privacy Policy</a>'
                                            ],
                                            $text
                                        );
                                    @endphp
                                    <p>{!! nl2br($text) !!}</p>
                                @endif
                            @endforeach
                        </section>
                    @empty
                        <section aria-labelledby="section-info-collect">
                            <h2 id="section-info-collect">1. Information we collect</h2>
                            <p>We may collect your contact details, account information, shipping and billing addresses...</p>
                        </section>
                    @endforelse

                    {{-- Back to top --}}
                    <div>
                        <a href="#main-content" class="np-back-to-top">
                            &uarr; Back to top
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.storefront>
