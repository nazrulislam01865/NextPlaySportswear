<x-layouts.storefront :seo="$seo">
    <div class="np-size-page np-guide-page">
        <div class="np-size-container">
            <nav class="np-size-breadcrumb" aria-label="Breadcrumb">
                <ol><li><a href="{{ route('home') }}">Home</a></li><li aria-current="page">Size Guide</li></ol>
            </nav>
            <header class="np-size-header">
                <h1 class="np-size-title">{{ data_get($content, 'hero.title', 'Size Guide') }}</h1>
                <p class="np-size-subtitle">{{ data_get($content, 'hero.description') }}</p>
            </header>
            <div class="np-size-card">
                <aside class="np-size-sidebar" aria-label="On this page">
                    <div class="np-guide-sticky-nav">
                        <h2 class="np-size-sidebar-title">On this page</h2>
                    <nav class="np-size-nav">
                        <a class="np-size-nav-link is-active" href="#before-measuring">{{ data_get($content, 'sections.0.title') }}</a>
                        <a class="np-size-nav-link" href="#how-to-measure">{{ data_get($content, 'sections.1.title') }}</a>
                        <a class="np-size-nav-link" href="#adult-tops">{{ data_get($content, 'charts.0.title') }}</a>
                        <a class="np-size-nav-link" href="#youth-tops">{{ data_get($content, 'charts.1.title') }}</a>
                        <a class="np-size-nav-link" href="#bottoms">{{ data_get($content, 'charts.2.title') }}</a>
                        <a class="np-size-nav-link" href="#fit-support">{{ data_get($content, 'fit.title') }}</a>
                    </nav>
                    </div>
                </aside>
                <div class="np-size-content">
                    @foreach(['before-measuring', 'how-to-measure'] as $index => $id)
                        <section id="{{ $id }}" class="np-size-section" aria-labelledby="size-heading-{{ $id }}">
                            <span class="np-size-number">{{ sprintf('%02d', $index + 1) }}</span>
                            <div class="np-size-section-body">
                                <h2 id="size-heading-{{ $id }}" class="np-size-section-heading">{{ data_get($content, 'sections.'.$index.'.title') }}</h2>
                                <p class="np-size-lead">{{ data_get($content, 'sections.'.$index.'.description') }}</p>
                                <ul class="np-size-tips">
                                    @foreach(data_get($content, 'sections.'.$index.'.items', []) as $item)
                                        <li><span class="np-size-check" aria-hidden="true">✓</span><span>{{ $item }}</span></li>
                                    @endforeach
                                </ul>
                            </div>
                        </section>
                    @endforeach
                    @foreach(['adult-tops', 'youth-tops', 'bottoms'] as $index => $id)
                        <section id="{{ $id }}" class="np-size-section" aria-labelledby="size-heading-{{ $id }}">
                            <span class="np-size-number">{{ sprintf('%02d', $index + 3) }}</span>
                            <div class="np-size-section-body">
                                <h2 id="size-heading-{{ $id }}" class="np-size-section-heading">{{ data_get($content, 'charts.'.$index.'.title') }}</h2>
                                <p class="np-size-lead">{{ data_get($content, 'charts.'.$index.'.description') }}</p>
                                <div class="np-size-table-scroll" role="region" aria-label="{{ data_get($content, 'charts.'.$index.'.title') }} size chart" tabindex="0">
                                    <table class="np-size-table">
                                        <thead><tr><th scope="col">Size</th><th scope="col">Chest</th><th scope="col">Waist</th><th scope="col">Hips</th><th scope="col">Approx. height / inseam</th></tr></thead>
                                        <tbody>
                                            @foreach(data_get($content, 'charts.'.$index.'.rows', []) as $row)
                                                <tr>
                                                    @foreach($row as $cell)
                                                        @if($loop->first)<th scope="row">{{ $cell }}</th>@else<td>{{ $cell }}</td>@endif
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>
                    @endforeach
                    <section id="fit-support" class="np-size-section" aria-labelledby="size-heading-fit-support">
                        <span class="np-size-number">06</span>
                        <div class="np-size-section-body">
                            <h2 id="size-heading-fit-support" class="np-size-section-heading">{{ data_get($content, 'fit.title') }}</h2>
                            <p class="np-size-lead">{{ data_get($content, 'fit.description') }}</p>
                            <ul class="np-size-tips">
                                @foreach(data_get($content, 'fit.tips', []) as $tip)
                                    <li><span class="np-size-check" aria-hidden="true">✓</span><span>{{ $tip }}</span></li>
                                @endforeach
                            </ul>
                            <a href="{{ route('contact') }}" class="btn btn-secondary nps-template-cta np-size-action">{{ data_get($content, 'fit.cta_text', 'Contact Support') }} <span aria-hidden="true">→</span></a>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-layouts.storefront>
