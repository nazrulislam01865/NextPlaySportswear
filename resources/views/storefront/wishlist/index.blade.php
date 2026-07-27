<x-layouts.storefront :seo="$seo">
    <style>
        .np-wishlist-page {
            --wishlist-navy: #15345d;
            --wishlist-dark: #0d2545;
            --wishlist-blue: #2467b7;
            --wishlist-red: #e91d33;
            --wishlist-ink: #111827;
            --wishlist-muted: #64748b;
            --wishlist-border: #dbe3ee;
            --wishlist-soft: #f4f7fb;
            background: linear-gradient(180deg, #f7f9fc 0%, #ffffff 74%);
            padding: 36px 0 64px;
            min-height: 68vh;
        }

        .np-wishlist-page *,
        .np-wishlist-page *::before,
        .np-wishlist-page *::after {
            box-sizing: border-box;
        }

        .np-wishlist-page .hidden {
            display: none !important;
        }

        .np-wishlist-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
            color: var(--wishlist-muted);
            font-size: 13px;
            font-weight: 700;
        }

        .np-wishlist-breadcrumb a {
            color: inherit;
            text-decoration: none;
            transition: color .2s ease;
        }

        .np-wishlist-breadcrumb a:hover,
        .np-wishlist-breadcrumb a:focus-visible {
            color: var(--wishlist-red);
        }

        .np-wishlist-hero {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            padding-bottom: 26px;
            border-bottom: 1px solid var(--wishlist-border);
        }

        .np-wishlist-eyebrow {
            margin: 0;
            color: var(--wishlist-red);
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .18em;
            text-transform: uppercase;
        }

        .np-wishlist-title {
            margin: 8px 0 0;
            color: var(--wishlist-ink);
            font-family: "Arial Narrow", "Roboto Condensed", Impact, sans-serif;
            font-size: clamp(38px, 5vw, 58px);
            font-weight: 900;
            line-height: .95;
            letter-spacing: -.025em;
            text-transform: uppercase;
        }

        .np-wishlist-intro {
            max-width: 720px;
            margin: 14px 0 0;
            color: #52637a;
            font-size: 15px;
            font-weight: 600;
            line-height: 1.75;
        }

        .np-wishlist-count-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 44px;
            padding: 10px 16px;
            flex: 0 0 auto;
            border: 1px solid var(--wishlist-border);
            border-radius: 999px;
            background: #fff;
            color: var(--wishlist-navy);
            box-shadow: 0 8px 24px rgba(15, 35, 63, .07);
            font-size: 14px;
            font-weight: 900;
        }

        .np-wishlist-count-pill svg {
            width: 18px;
            height: 18px;
            color: var(--wishlist-red);
        }

        .np-wishlist-guest-note {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-top: 22px;
            padding: 15px 17px;
            border: 1px solid #bfdbfe;
            border-radius: 16px;
            background: #eff6ff;
            color: #334155;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.55;
        }

        .np-wishlist-guest-note p {
            margin: 0;
        }

        .np-wishlist-guest-link {
            display: inline-flex;
            min-height: 40px;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            padding: 9px 16px;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            background: #fff;
            color: var(--wishlist-navy);
            text-decoration: none;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .05em;
            transition: border-color .2s ease, color .2s ease, transform .2s ease;
        }

        .np-wishlist-guest-link:hover {
            border-color: var(--wishlist-blue);
            color: var(--wishlist-blue);
            transform: translateY(-1px);
        }

        .np-wishlist-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 350px;
            align-items: start;
            gap: 24px;
            margin-top: 24px;
        }

        .np-wishlist-main {
            min-width: 0;
        }

        .np-wishlist-loading,
        .np-wishlist-empty {
            width: 100%;
            border: 1px solid var(--wishlist-border);
            border-radius: 24px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(15, 35, 63, .06);
            text-align: center;
        }

        .np-wishlist-loading {
            padding: 40px 24px;
            color: var(--wishlist-ink);
            font-size: 15px;
            font-weight: 900;
        }

        .np-wishlist-items {
            display: grid;
            gap: 16px;
            min-width: 0;
        }

        .np-wishlist-item {
            position: relative;
            width: 100%;
            min-width: 0;
            overflow: hidden;
            border: 1px solid var(--wishlist-border);
            border-radius: 24px;
            background: #fff;
            box-shadow: 0 10px 28px rgba(15, 35, 63, .055);
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .np-wishlist-item:hover {
            border-color: #b8c6d9;
            box-shadow: 0 16px 36px rgba(15, 35, 63, .09);
            transform: translateY(-2px);
        }

        .np-wishlist-card-grid {
            display: grid;
            grid-template-columns: 178px minmax(0, 1fr) 154px;
            min-width: 0;
        }

        .np-wishlist-media {
            display: grid;
            min-width: 0;
            min-height: 190px;
            place-items: center;
            padding: 16px;
            border-right: 1px solid #e7edf4;
            background: linear-gradient(145deg, #f8fafc 0%, #edf3f9 100%);
            text-decoration: none;
        }

        .np-wishlist-image-frame {
            display: grid;
            width: 100%;
            aspect-ratio: 1 / 1;
            place-items: center;
            overflow: hidden;
            border: 1px solid #e0e8f1;
            border-radius: 18px;
            background: #fff;
        }

        .np-wishlist-image {
            display: block;
            width: 100%;
            height: 100%;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            padding: 10px;
            transition: transform .25s ease;
        }

        .np-wishlist-item:hover .np-wishlist-image {
            transform: scale(1.035);
        }

        .np-wishlist-content {
            min-width: 0;
            padding: 22px 22px 20px;
        }

        .np-wishlist-meta-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .np-wishlist-category,
        .np-wishlist-saved-label {
            display: inline-flex;
            align-items: center;
            min-height: 25px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .09em;
            line-height: 1;
            text-transform: uppercase;
        }

        .np-wishlist-category {
            background: #eaf2ff;
            color: var(--wishlist-blue);
        }

        .np-wishlist-saved-label {
            gap: 5px;
            background: #fff0f2;
            color: var(--wishlist-red);
        }

        .np-wishlist-saved-label svg {
            width: 12px;
            height: 12px;
        }

        .np-wishlist-product-title {
            display: -webkit-box;
            overflow: hidden;
            margin-top: 12px;
            color: var(--wishlist-ink);
            text-decoration: none;
            font-size: 20px;
            font-weight: 900;
            line-height: 1.35;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            transition: color .2s ease;
        }

        .np-wishlist-product-title:hover,
        .np-wishlist-product-title:focus-visible {
            color: var(--wishlist-red);
        }

        .np-wishlist-product-summary {
            display: -webkit-box;
            overflow: hidden;
            margin: 9px 0 0;
            color: var(--wishlist-muted);
            font-size: 13px;
            font-weight: 600;
            line-height: 1.65;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .np-wishlist-product-facts {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 14px;
            margin-top: 16px;
        }

        .np-wishlist-price {
            margin: 0;
            color: var(--wishlist-muted);
            font-size: 12px;
            font-weight: 800;
        }

        .np-wishlist-price strong {
            margin-left: 4px;
            color: var(--wishlist-ink);
            font-size: 20px;
            font-weight: 900;
        }

        .np-wishlist-minimum {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #475569;
            font-size: 12px;
            font-weight: 800;
        }

        .np-wishlist-minimum::before {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: var(--wishlist-blue);
            content: "";
        }

        .np-wishlist-actions {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 10px;
            min-width: 0;
            padding: 20px;
            border-left: 1px solid #e7edf4;
            background: #fbfcfe;
        }

        .np-wishlist-button {
            display: inline-flex;
            width: 100%;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 14px;
            border: 1px solid transparent;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            font: inherit;
            font-size: 12px;
            font-weight: 900;
            line-height: 1.1;
            text-align: center;
            transition: background .2s ease, border-color .2s ease, color .2s ease, transform .2s ease, box-shadow .2s ease;
        }

        .np-wishlist-button svg {
            width: 17px;
            height: 17px;
            flex: 0 0 auto;
        }

        .np-wishlist-button-primary {
            border-color: var(--wishlist-red);
            background: var(--wishlist-red);
            color: #fff;
            box-shadow: 0 8px 18px rgba(233, 29, 51, .18);
        }

        .np-wishlist-button-primary:hover {
            border-color: #c9182b;
            background: #c9182b;
            transform: translateY(-1px);
        }

        .np-wishlist-button-secondary {
            border-color: #d3dce8;
            background: #fff;
            color: var(--wishlist-red);
        }

        .np-wishlist-button-secondary:hover {
            border-color: #f2aab3;
            background: #fff5f6;
            transform: translateY(-1px);
        }

        .np-wishlist-button:focus-visible,
        .np-wishlist-product-title:focus-visible,
        .np-wishlist-media:focus-visible,
        .np-wishlist-guest-link:focus-visible,
        .np-wishlist-breadcrumb a:focus-visible {
            outline: 3px solid rgba(36, 103, 183, .28);
            outline-offset: 3px;
        }

        .np-wishlist-button:disabled {
            cursor: wait;
            opacity: .6;
            transform: none;
        }

        .np-wishlist-empty {
            padding: 62px 28px;
            border-style: dashed;
        }

        .np-wishlist-empty-icon {
            display: grid;
            width: 68px;
            height: 68px;
            margin: 0 auto;
            place-items: center;
            border-radius: 50%;
            background: #eef3f8;
            color: var(--wishlist-navy);
        }

        .np-wishlist-empty-icon svg {
            width: 31px;
            height: 31px;
        }

        .np-wishlist-empty h2 {
            margin: 18px 0 0;
            color: var(--wishlist-ink);
            font-size: 25px;
            font-weight: 900;
        }

        .np-wishlist-empty p {
            max-width: 540px;
            margin: 9px auto 0;
            color: var(--wishlist-muted);
            font-size: 14px;
            font-weight: 600;
            line-height: 1.65;
        }

        .np-wishlist-empty .np-wishlist-button {
            width: auto;
            margin-top: 22px;
            padding-inline: 24px;
        }

        .np-wishlist-summary {
            position: sticky;
            top: 132px;
            overflow: hidden;
            border-radius: 24px;
            background: linear-gradient(145deg, var(--wishlist-dark), #1b477a);
            color: #fff;
            box-shadow: 0 18px 40px rgba(13, 37, 69, .22);
        }

        .np-wishlist-summary-head {
            padding: 25px 25px 22px;
            border-bottom: 1px solid rgba(255,255,255,.12);
        }

        .np-wishlist-summary-eyebrow {
            margin: 0;
            color: #b8d5ff;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .18em;
            text-transform: uppercase;
        }

        .np-wishlist-summary h2 {
            margin: 8px 0 0;
            font-family: "Arial Narrow", "Roboto Condensed", Impact, sans-serif;
            font-size: 32px;
            font-weight: 900;
            line-height: 1.03;
            text-transform: uppercase;
        }

        .np-wishlist-summary-body {
            padding: 22px 25px 25px;
        }

        .np-wishlist-summary-copy {
            margin: 0;
            color: #d8e8fb;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.7;
        }

        .np-wishlist-summary-list {
            display: grid;
            gap: 11px;
            margin: 18px 0 0;
            padding: 0;
            list-style: none;
        }

        .np-wishlist-summary-list li {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            color: #eef6ff;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.45;
        }

        .np-wishlist-summary-list span {
            display: grid;
            width: 20px;
            height: 20px;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 50%;
            background: rgba(255,255,255,.12);
            color: #fff;
            font-size: 11px;
        }

        .np-wishlist-summary-actions {
            display: grid;
            gap: 10px;
            margin-top: 22px;
        }

        .np-wishlist-summary .np-wishlist-button-secondary {
            border-color: rgba(255,255,255,.42);
            background: transparent;
            color: #fff;
        }

        .np-wishlist-summary .np-wishlist-button-secondary:hover {
            border-color: rgba(255,255,255,.72);
            background: rgba(255,255,255,.08);
        }

        @media (max-width: 1120px) {
            .np-wishlist-layout {
                grid-template-columns: minmax(0, 1fr) 310px;
            }

            .np-wishlist-card-grid {
                grid-template-columns: 155px minmax(0, 1fr);
            }

            .np-wishlist-actions {
                grid-column: 1 / -1;
                flex-direction: row;
                padding: 15px 18px;
                border-top: 1px solid #e7edf4;
                border-left: 0;
            }
        }

        @media (max-width: 940px) {
            .np-wishlist-layout {
                grid-template-columns: 1fr;
            }

            .np-wishlist-summary {
                position: static;
            }

            .np-wishlist-summary-actions {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 680px) {
            .np-wishlist-page {
                padding: 24px 0 44px;
            }

            .np-wishlist-hero {
                align-items: flex-start;
                flex-direction: column;
                gap: 17px;
            }

            .np-wishlist-title {
                font-size: 40px;
            }

            .np-wishlist-intro {
                font-size: 14px;
            }

            .np-wishlist-guest-note {
                align-items: stretch;
                flex-direction: column;
            }

            .np-wishlist-guest-link {
                width: 100%;
            }

            .np-wishlist-card-grid {
                grid-template-columns: 1fr;
            }

            .np-wishlist-media {
                min-height: 0;
                padding: 16px;
                border-right: 0;
                border-bottom: 1px solid #e7edf4;
            }

            .np-wishlist-image-frame {
                width: min(100%, 290px);
            }

            .np-wishlist-content {
                padding: 19px 18px 18px;
            }

            .np-wishlist-product-title {
                font-size: 19px;
            }

            .np-wishlist-actions {
                grid-column: auto;
                flex-direction: row;
                padding: 15px 18px 18px;
            }

            .np-wishlist-summary-head,
            .np-wishlist-summary-body {
                padding-inline: 20px;
            }

            .np-wishlist-summary-actions {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 420px) {
            .np-wishlist-actions {
                flex-direction: column;
            }

            .np-wishlist-count-pill {
                width: 100%;
            }
        }
    </style>

    <section
        class="np-wishlist-page"
        data-wishlist-page
        data-authenticated="{{ $isAuthenticatedCustomer ? '1' : '0' }}"
        data-storage-key="{{ $guestStorageKey }}"
        data-products-endpoint="{{ $guestProductsEndpoint }}"
        data-login-url="{{ $loginUrl }}"
    >
        <div class="site-container">
            <nav class="np-wishlist-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Wishlist</span>
            </nav>

            <header class="np-wishlist-hero">
                <div>
                    <p class="np-wishlist-eyebrow">Saved for later</p>
                    <h1 class="np-wishlist-title">My Wishlist</h1>
                    <p class="np-wishlist-intro">
                        Keep your favorite uniforms, apparel, and team gear together before configuring your custom order.
                    </p>
                </div>

                <div class="np-wishlist-count-pill" aria-live="polite">
                    <svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"></path>
                    </svg>
                    <span data-wishlist-page-count>{{ $items->count() }}</span>
                    <span data-wishlist-page-count-label>{{ $items->count() === 1 ? 'item' : 'items' }}</span>
                </div>
            </header>

            @unless($isAuthenticatedCustomer)
                <div class="np-wishlist-guest-note">
                    <p>Your wishlist is saved in this browser. Sign in to keep saved products available across devices.</p>
                    <a href="{{ $loginUrl }}" class="np-wishlist-guest-link">Sign In</a>
                </div>
            @endunless

            <div class="np-wishlist-layout">
                <main class="np-wishlist-main">
                    <div data-wishlist-loading class="np-wishlist-loading {{ $isAuthenticatedCustomer ? 'hidden' : '' }}">
                        Loading your saved products…
                    </div>

                    <div data-wishlist-items class="np-wishlist-items">
                        @foreach($items as $item)
                            <article class="np-wishlist-item" data-wishlist-item data-product-key="{{ $item['id'] }}">
                                <div class="np-wishlist-card-grid">
                                    <a href="{{ $item['url'] }}" class="np-wishlist-media" aria-label="View {{ $item['title'] }}">
                                        <span class="np-wishlist-image-frame">
                                            <img
                                                src="{{ $item['image'] }}"
                                                alt="{{ $item['alt'] }}"
                                                class="np-wishlist-image"
                                                loading="lazy"
                                            >
                                        </span>
                                    </a>

                                    <div class="np-wishlist-content">
                                        <div class="np-wishlist-meta-row">
                                            @if(filled($item['category']))
                                                <span class="np-wishlist-category">{{ $item['category'] }}</span>
                                            @endif
                                            <span class="np-wishlist-saved-label">
                                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"></path></svg>
                                                Saved
                                            </span>
                                        </div>

                                        <a href="{{ $item['url'] }}" class="np-wishlist-product-title">{{ $item['title'] }}</a>

                                        @if(filled($item['summary']))
                                            <p class="np-wishlist-product-summary">{{ $item['summary'] }}</p>
                                        @endif

                                        <div class="np-wishlist-product-facts">
                                            <p class="np-wishlist-price">Starting at <strong>${{ number_format($item['price'], 2) }}</strong> each</p>
                                            <span class="np-wishlist-minimum">Minimum {{ $item['minimum_quantity'] }} pcs</span>
                                        </div>
                                    </div>

                                    <div class="np-wishlist-actions">
                                        <a href="{{ $item['url'] }}" class="np-wishlist-button np-wishlist-button-primary">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                                            View Product
                                        </a>
                                        <button
                                            type="button"
                                            class="np-wishlist-button np-wishlist-button-secondary"
                                            data-wishlist-remove
                                            data-product-key="{{ $item['id'] }}"
                                            data-endpoint="{{ $item['remove_endpoint'] }}"
                                            aria-label="Remove {{ $item['title'] }} from wishlist"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5"></path></svg>
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div data-wishlist-empty class="np-wishlist-empty {{ $items->isEmpty() ? '' : 'hidden' }}">
                        <div class="np-wishlist-empty-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"></path></svg>
                        </div>
                        <h2>Your wishlist is empty</h2>
                        <p>Use the heart or Save button on any product page to keep products here for later.</p>
                        <a href="{{ route('products.index') }}" class="np-wishlist-button np-wishlist-button-primary">Browse Products</a>
                    </div>
                </main>

                <aside class="np-wishlist-summary">
                    <div class="np-wishlist-summary-head">
                        <p class="np-wishlist-summary-eyebrow">Next step</p>
                        <h2>Build Your Team Order</h2>
                    </div>
                    <div class="np-wishlist-summary-body">
                        <p class="np-wishlist-summary-copy">
                            Open any saved product to choose colors, sizes, artwork, production speed, and shipping.
                        </p>
                        <ul class="np-wishlist-summary-list">
                            <li><span>1</span> Choose a saved product</li>
                            <li><span>2</span> Configure your team requirements</li>
                            <li><span>3</span> Add the finished setup to cart</li>
                        </ul>
                        <div class="np-wishlist-summary-actions">
                            <a href="{{ route('products.index') }}" class="np-wishlist-button np-wishlist-button-primary">Continue Shopping</a>
                            <a href="{{ route('cart.index') }}" class="np-wishlist-button np-wishlist-button-secondary">View Cart</a>
                        </div>
                    </div>
                </aside>
            </div>
        </div>

        <template data-wishlist-guest-template>
            <article class="np-wishlist-item" data-wishlist-item>
                <div class="np-wishlist-card-grid">
                    <a data-wishlist-product-link class="np-wishlist-media" aria-label="View saved product">
                        <span class="np-wishlist-image-frame">
                            <img data-wishlist-product-image class="np-wishlist-image" alt="" loading="lazy" decoding="async">
                        </span>
                    </a>

                    <div class="np-wishlist-content">
                        <div class="np-wishlist-meta-row">
                            <span data-wishlist-product-category class="np-wishlist-category"></span>
                            <span class="np-wishlist-saved-label">
                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"></path></svg>
                                Saved
                            </span>
                        </div>

                        <a data-wishlist-product-title class="np-wishlist-product-title"></a>
                        <p data-wishlist-product-summary class="np-wishlist-product-summary"></p>

                        <div class="np-wishlist-product-facts">
                            <p class="np-wishlist-price">Starting at <strong data-wishlist-product-price></strong> each</p>
                            <span class="np-wishlist-minimum">Ready to customize</span>
                        </div>
                    </div>

                    <div class="np-wishlist-actions">
                        <a data-wishlist-view-product class="np-wishlist-button np-wishlist-button-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                            View Product
                        </a>
                        <button type="button" class="np-wishlist-button np-wishlist-button-secondary" data-wishlist-remove>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5"></path></svg>
                            Remove
                        </button>
                    </div>
                </div>
            </article>
        </template>
    </section>
</x-layouts.storefront>
