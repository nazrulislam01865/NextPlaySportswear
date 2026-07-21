@php
    $whatsappDigits = preg_replace('/\D+/', '', (string) config('storefront.whatsapp'));
    $whatsappUrl = config('storefront.whatsapp_url') ?: ($whatsappDigits ? 'https://wa.me/'.$whatsappDigits : '#');
    $socialLinks = collect(config('storefront.social', []))->filter(fn ($url) => filled($url) && $url !== '#');
@endphp

<div class="np-utility-bar" aria-label="Store utility links">
    <div class="np-header-shell np-utility-inner">
        <p class="np-utility-message" aria-label="Custom team gear. Built for every game.">
            <span class="np-utility-star" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                    <path d="M12 2.5l2.45 5.54 6.05.53-4.58 3.95 1.38 5.86L12 15.25 6.7 18.38l1.38-5.86L3.5 8.57l6.05-.53L12 2.5z"/>
                </svg>
            </span>
            <span class="np-utility-marquee" aria-hidden="true">
                <span class="np-utility-marquee-track">
                    <span class="np-utility-marquee-item">CUSTOM TEAM GEAR <b>•</b> BUILT FOR EVERY GAME</span>
                    <span class="np-utility-marquee-item">CUSTOM TEAM GEAR <b>•</b> BUILT FOR EVERY GAME</span>
                </span>
            </span>
        </p>

        <nav class="np-utility-links" aria-label="Header utility navigation">
            <a href="{{ route('orders.track') }}" class="np-utility-link" data-header-analytics="header_cta_click" data-header-analytics-label="track_order">
                <span class="np-utility-link-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.5 3H7a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7.5L14.5 3z"/>
                        <path d="M14 3v5h5"/>
                        <path d="M8 11h8"/>
                        <path d="M8 15h5"/>
                    </svg>
                </span>
                <span>Track Order</span>
            </a>

            <a href="{{ route('quote.request') }}" class="np-utility-link" data-header-analytics="header_cta_click" data-header-analytics-label="bulk_quotes">
                <span class="np-utility-link-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h8.3L20 10.2V18.5A1.5 1.5 0 0 1 18.5 20h-13A1.5 1.5 0 0 1 4 18.5v-13z"/>
                        <path d="M13 4v6h6"/>
                        <path d="M8 12h8"/>
                        <path d="M8 16h6"/>
                    </svg>
                </span>
                <span>Bulk Quotes</span>
            </a>

            <a href="{{ $whatsappUrl }}" class="np-utility-link np-whatsapp-link" target="_blank" rel="noopener noreferrer" data-header-analytics="header_cta_click" data-header-analytics-label="whatsapp">
                <span class="np-utility-link-icon np-whatsapp-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 32 32" fill="currentColor" focusable="false">
                        <path d="M16.04 3C9.48 3 4.15 8.33 4.15 14.89c0 2.1.55 4.15 1.6 5.96L4 29l8.34-1.69a11.86 11.86 0 0 0 5.7 1.45h.01c6.56 0 11.89-5.33 11.89-11.89 0-3.18-1.24-6.17-3.49-8.41A11.81 11.81 0 0 0 16.04 3Zm0 2.01c2.64 0 5.12 1.03 6.99 2.9a9.82 9.82 0 0 1 2.9 6.98c0 5.45-4.43 9.88-9.88 9.88h-.01a9.83 9.83 0 0 1-5.02-1.38l-.36-.21-4.95 1 1.03-4.83-.24-.38a9.82 9.82 0 0 1-1.51-5.08c0-5.45 4.43-9.88 9.88-9.88Zm-3.09 5.43c-.22-.49-.45-.5-.66-.51h-.56c-.2 0-.51.07-.78.36-.27.29-1.03 1-1.03 2.45s1.06 2.85 1.2 3.04c.15.2 2.04 3.26 5.05 4.44 2.5.98 3.01.78 3.55.73.54-.05 1.75-.72 2-1.41.25-.69.25-1.28.18-1.41-.07-.12-.27-.2-.56-.34-.29-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.66.15-.2.29-.76.96-.93 1.16-.17.2-.34.22-.64.07-.29-.15-1.23-.45-2.34-1.44-.87-.77-1.45-1.73-1.62-2.02-.17-.29-.02-.45.13-.59.13-.13.29-.34.44-.51.15-.17.2-.29.29-.49.1-.2.05-.37-.02-.51-.07-.15-.65-1.61-.92-2.19Z"/>
                    </svg>
                </span>
                <span>WhatsApp Us</span>
            </a>

            @if($socialLinks->isNotEmpty())
                <span class="np-utility-divider" aria-hidden="true"></span>
                <span class="np-follow-label">Follow us</span>

                <div class="np-social-links">
                    @foreach($socialLinks as $network => $url)
                        @php($networkLabel = str($network)->headline()->toString())
                        <a href="{{ $url }}" class="np-social-link" target="_blank" rel="noopener noreferrer" aria-label="Follow NextPlay Sportswear on {{ $networkLabel }}" data-header-analytics="header_social_click" data-header-analytics-label="{{ $network }}">
                            <span class="sr-only">{{ $networkLabel }}</span>
                            <span class="np-social-icon" aria-hidden="true">
                                @switch($network)
                                    @case('instagram')
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3.2" y="3.2" width="17.6" height="17.6" rx="5"></rect>
                                            <circle cx="12" cy="12" r="4.1"></circle>
                                            <circle cx="17.4" cy="6.7" r="1"></circle>
                                        </svg>
                                        @break
                                    @case('facebook')
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M13.5 21v-7h2.6l.4-3h-3V9.1c0-.87.24-1.47 1.5-1.47H16.7V4.96c-.3-.04-1.33-.12-2.53-.12-2.5 0-4.22 1.53-4.22 4.35V11H7.1v3h2.85v7h3.55Z"></path>
                                        </svg>
                                        @break
                                    @case('tiktok')
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M14.7 3c.22 1.86 1.28 3.31 3.1 4.1.73.32 1.47.47 2.2.49v2.93a8.28 8.28 0 0 1-2.62-.42 7.27 7.27 0 0 1-2.56-1.47v6.59c0 3.53-2.8 6.2-6.34 6.2a6.22 6.22 0 0 1-2.87-.7A6.14 6.14 0 0 1 2 15.2c0-3.53 2.8-6.2 6.33-6.2.31 0 .62.03.92.08v3.14a3.18 3.18 0 0 0-.92-.14 3.15 3.15 0 0 0-3.18 3.12c0 1.12.58 2.11 1.46 2.67.5.31 1.09.49 1.72.49 1.75 0 3.08-1.36 3.08-3.14V3h3.29Z"></path>
                                        </svg>
                                        @break
                                    @case('youtube')
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M21.6 7.2a2.96 2.96 0 0 0-2.08-2.09C17.67 4.6 12 4.6 12 4.6s-5.67 0-7.52.51A2.96 2.96 0 0 0 2.4 7.2C1.9 9.05 1.9 12 1.9 12s0 2.95.5 4.8a2.96 2.96 0 0 0 2.08 2.09C6.33 19.4 12 19.4 12 19.4s5.67 0 7.52-.51a2.96 2.96 0 0 0 2.08-2.09c.5-1.85.5-4.8.5-4.8s0-2.95-.5-4.8ZM10.2 15.05V8.95L15.4 12l-5.2 3.05Z"></path>
                                        </svg>
                                        @break
                                    @default
                                        <span>{{ strtoupper(substr((string) $network, 0, 1)) }}</span>
                                @endswitch
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </nav>
    </div>
</div>
