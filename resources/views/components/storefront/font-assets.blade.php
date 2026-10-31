@once
    {{-- Expose is loaded once via storefront-theme.css at /fonts/expose/*.woff2.
         Install the four supplied Expose fonts with deploy/install-expose-fonts.sh.
         Segoe UI is the only named fallback. No third-party CDN is required. --}}
    <link rel="preload" href="{{ asset('fonts/expose/Expose-Regular.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/expose/Expose-Bold.woff2') }}" as="font" type="font/woff2" crossorigin>
@endonce
