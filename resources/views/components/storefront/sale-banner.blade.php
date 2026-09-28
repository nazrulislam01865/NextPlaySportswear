@props(['banner', 'class' => ''])
@if($banner)
    @php
        $destination = (string) ($banner['destination_link'] ?? '/sale');
        $desktop = (string) ($banner['desktop_image_url'] ?? '');
        $mobile = (string) ($banner['mobile_image_url'] ?? '');
        $heading = trim((string) ($banner['heading'] ?? ''));
        $ctaLabel = trim((string) ($banner['cta_label'] ?? ''));
    @endphp

    @once
        <style>
            /* Compact catalog banner: matches the Sale/All Products listing rhythm.
               The artwork fills a controlled slot instead of using its natural image height. */
            .np-store-sale-banner{
                position:relative;
                display:block;
                width:100%;
                aspect-ratio:4.75/1;
                min-height:0;
                max-height:190px;
                overflow:hidden;
                border:0;
                border-radius:var(--np-button-radius,.4rem);
                background:var(--np-color-soft,#eef2f7);
                box-shadow:none;
                isolation:isolate;
            }
            .np-store-sale-banner picture{
                position:absolute;
                inset:0;
                display:block;
                width:100%;
                height:100%;
            }
            .np-store-sale-banner img{
                display:block;
                width:100%;
                height:100%;
                object-fit:cover;
                object-position:center;
                background:var(--np-color-soft,#eef2f7);
            }
            .np-store-sale-banner__shade{
                position:absolute;
                inset:0;
                z-index:1;
                background:linear-gradient(90deg,rgba(3,18,42,.76) 0%,rgba(3,18,42,.48) 35%,rgba(3,18,42,.08) 68%,rgba(3,18,42,0) 100%);
                pointer-events:none;
            }
            .np-store-sale-banner__content{
                position:absolute;
                z-index:2;
                left:clamp(.9rem,2.2vw,1.55rem);
                top:50%;
                transform:translateY(-50%);
                display:grid;
                justify-items:start;
                gap:.45rem;
                max-width:min(58%,440px);
                color:#fff;
            }
            .np-store-sale-banner__heading{
                margin:0;
                color:#fff;
                font-family:inherit;
                font-size:clamp(1rem,1.65vw,1.7rem);
                font-weight:800;
                line-height:1.05;
                letter-spacing:-.02em;
                text-shadow:0 2px 6px rgba(0,0,0,.22);
            }
            .np-store-sale-banner__cta{
                display:inline-flex;
                min-height:2rem;
                align-items:center;
                justify-content:center;
                border-radius:var(--np-button-radius,.4rem);
                background:var(--np-color-secondary,var(--np-color-primary,#cf5d3c));
                padding:.42rem .72rem;
                color:#fff;
                font-family:inherit;
                font-size:.76rem;
                font-weight:800;
                line-height:1;
                box-shadow:none;
            }
            .np-store-sale-banner:focus-visible{
                outline:3px solid rgb(var(--np-color-secondary-rgb,36 103 183)/.3);
                outline-offset:3px;
            }

            @media(max-width:900px){
                .np-store-sale-banner{
                    aspect-ratio:4/1;
                    max-height:170px;
                }
            }

            @media(max-width:640px){
                .np-store-sale-banner{
                    aspect-ratio:5/4;
                    max-height:none;
                }
                .np-store-sale-banner__shade{
                    background:linear-gradient(0deg,rgba(3,18,42,.8) 0%,rgba(3,18,42,.32) 52%,rgba(3,18,42,.02) 100%);
                }
                .np-store-sale-banner__content{
                    left:1rem;
                    right:1rem;
                    top:auto;
                    bottom:1rem;
                    transform:none;
                    max-width:none;
                }
                .np-store-sale-banner__heading{
                    font-size:clamp(1.05rem,5vw,1.5rem);
                }
                .np-store-sale-banner__cta{
                    min-height:2.1rem;
                    padding:.48rem .72rem;
                    font-size:.78rem;
                }
            }
        </style>
    @endonce

    <a
        href="{{ $destination }}"
        class="np-store-sale-banner {{ $class }}"
        aria-label="{{ $ctaLabel ?: $heading ?: ($banner['name'] ?? 'Sale banner') }}"
    >
        <picture>
            @if(filled($mobile))
                <source media="(max-width: 640px)" srcset="{{ $mobile }}">
            @endif
            <img
                src="{{ $desktop }}"
                alt="{{ $banner['alt_text'] }}"
                loading="lazy"
            >
        </picture>

        @if($heading !== '' || $ctaLabel !== '')
            <span class="np-store-sale-banner__shade" aria-hidden="true"></span>
            <span class="np-store-sale-banner__content">
                @if($heading !== '')
                    <strong class="np-store-sale-banner__heading">{{ $heading }}</strong>
                @endif
                @if($ctaLabel !== '')
                    <span class="np-store-sale-banner__cta">{{ $ctaLabel }}</span>
                @endif
            </span>
        @endif
    </a>
@endif
