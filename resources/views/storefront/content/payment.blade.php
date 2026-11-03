@php
    $pageTitle = (string) data_get($content, 'hero.title', 'Payment Information');
    $sections = data_get($content, 'sections', []);
    $statusRows = data_get($content, 'status_table.rows', []);
    $statusColumns = data_get($content, 'status_table.columns', []);
    $currentBreadcrumb = trim((string) data_get($content, 'navigation.breadcrumb_label', '')) ?: $pageTitle;
@endphp
<x-layouts.storefront :seo="$seo">
    {{-- Share the actual Returns & Refunds policy layout: breadcrumb, header,
         centered white card, sticky navigation and type hierarchy. --}}
    <div class="np-returns-page np-payment-page" id="payment-document-top">
        <div class="np-returns-container site-container">
            <nav class="np-returns-breadcrumb" aria-label="Breadcrumb">
                <ol>
                    <li><a href="{{ route('home') }}">{{ data_get($content, 'navigation.home_label', 'Home') }}</a></li>
                    <li><span aria-current="page">{{ $currentBreadcrumb }}</span></li>
                </ol>
            </nav>

            <header class="np-returns-header">
                <h1 class="np-returns-title">{{ $pageTitle }}</h1>
                <p class="np-returns-subtitle">{{ data_get($content, 'hero.description') }}</p>
            </header>

            <div class="np-returns-card np-payment-card">
                <aside class="np-returns-sidebar np-payment-sidebar" aria-label="Payment information sections">
                    <nav class="np-guide-sticky-nav" aria-label="{{ data_get($content, 'navigation.sidebar_heading', 'On this page') }}">
                        <h2 class="np-sidebar-title">{{ data_get($content, 'navigation.sidebar_heading', 'On this page') }}</h2>
                        <ul class="np-sidebar-nav" id="np-payment-sidebar-nav">
                            @foreach($sections as $index => $section)
                                <li>
                                    <a class="np-sidebar-link np-payment-nav-link{{ $loop->first ? ' is-active' : '' }}"
                                       href="#payment-section-{{ $index + 1 }}"
                                       @if($loop->first) aria-current="location" @endif>
                                        <span>{{ data_get($section, 'nav_label', data_get($section, 'title')) }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                </aside>

                <article class="np-returns-content np-payment-document" aria-label="{{ $pageTitle }}">
                    @foreach($sections as $index => $section)
                        <x-storefront.content.document-section
                            :id="'payment-section-' . ($index + 1)"
                            :number="str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)"
                            :title="(string) data_get($section, 'title', '')"
                        >
                            @if(filled(data_get($section, 'content')))
                                @foreach(preg_split('/\n\s*\n/', trim((string) data_get($section, 'content'))) ?: [] as $paragraph)
                                    <p class="np-section-text np-payment-section__copy">{{ $paragraph }}</p>
                                @endforeach
                            @endif

                            @if($index === 5)
                                <div class="np-payment-table-scroll">
                                    <table class="np-payment-status-table" style="--np-payment-columns: {{ count($statusColumns) }}" aria-labelledby="payment-section-6-heading">
                                        <thead>
                                            <tr>
                                                @foreach($statusColumns as $heading)
                                                    <th scope="col">{{ $heading }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($statusRows as $row)
                                                <tr>
                                                    @foreach($statusColumns as $columnIndex => $heading)
                                                        @if($loop->first)
                                                            <th scope="row">{{ data_get($row, 'cells.'.$columnIndex, '') }}</th>
                                                        @else
                                                            <td>{{ data_get($row, 'cells.'.$columnIndex, '') }}</td>
                                                        @endif
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @if($index === 6)
                                <a class="np-payment-refund-link" href="{{ data_get($content, 'refund_link.href', route('returns')) }}">{{ data_get($content, 'refund_link.label') }}</a>
                            @endif
                        </x-storefront.content.document-section>
                    @endforeach
                </article>
            </div>

            <section class="np-payment-help" aria-label="{{ data_get($content, 'support.title') }}">
                <div class="np-payment-help__copy">
                    <h2>{{ data_get($content, 'support.title') }}</h2>
                    <p>{{ data_get($content, 'support.description') }}</p>
                </div>
                <div class="np-payment-help__actions">
                    <a class="btn btn-red nps-template-cta" href="{{ data_get($content, 'support.primary_href', route('contact')) }}">{{ data_get($content, 'support.primary_label') }}</a>
                    <a class="np-payment-help__secondary" href="{{ data_get($content, 'support.secondary_href', route('orders.track')) }}">{{ data_get($content, 'support.secondary_label') }}</a>
                </div>
            </section>
        </div>
    </div>
    <script defer src="{{ asset('js/nextplay-payment-navigation.js') }}?v=20261103b"></script>
</x-layouts.storefront>
