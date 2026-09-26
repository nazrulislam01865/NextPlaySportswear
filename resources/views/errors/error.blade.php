<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#061F44">
    <title>{{ $code }} — {{ $title }} | {{ config('storefront.name', 'NextPlay Sportswear') }}</title>
    @php
        $storefrontAsset = null;
        $manifestPath = public_path('build/manifest.json');
        if (is_file($manifestPath)) {
            try {
                $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
                $storefrontAsset = $manifest['resources/css/storefront.css']['file'] ?? null;
            } catch (Throwable) {
                $storefrontAsset = null;
            }
        }
    @endphp
    @if ($storefrontAsset)
        <link rel="stylesheet" href="{{ asset('build/'.$storefrontAsset) }}">
    @endif
    <style>
        :root {
            --error-navy: var(--np-color-primary, #061F44);
            --error-orange: var(--np-color-secondary, #CF5D38);
            --error-ink: var(--np-color-heading, #061F44);
            --error-muted: var(--np-color-muted, #64748B);
            --error-line: var(--np-color-border, #E2E8F0);
            --error-soft: var(--np-color-soft, #F7F5F2);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            grid-template-rows: auto 1fr auto;
            background: var(--error-soft);
            color: var(--error-ink);
            font-family: var(--np-font-body, "Expose", ui-sans-serif, system-ui, sans-serif);
            font-size: var(--np-body-2-size, 14px);
            line-height: var(--np-body-2-line-height, 1.4);
            letter-spacing: 0;
        }
        .bar {
            padding: 10px 20px;
            background: var(--error-navy);
            color: #fff;
            text-align: center;
            font-size: var(--np-body-2-size, 14px);
            font-weight: var(--np-font-weight-medium, 500);
        }
        .head { border-bottom: 1px solid var(--error-line); background: #fff; }
        .headin {
            width: min(1060px, calc(100% - 32px));
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 17px 0;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--error-ink);
            font-family: var(--np-font-heading, "Expose", ui-sans-serif, system-ui, sans-serif);
            font-size: var(--np-title-3-size, 24px);
            font-weight: var(--np-title-3-weight, 900);
            line-height: var(--np-title-3-line-height, 1.2);
            text-decoration: none;
        }
        .mark {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border: 3px solid var(--error-orange);
            border-radius: 9px;
            color: var(--error-orange);
        }
        .logo b { color: var(--error-orange); }
        .home {
            border: 1px solid var(--error-line);
            border-radius: var(--np-button-radius, .4rem);
            padding: 10px 15px;
            color: var(--error-ink);
            font-weight: var(--np-font-weight-medium, 500);
            text-decoration: none;
        }
        main { display: grid; place-items: center; padding: 44px 16px; }
        .card {
            width: min(880px, 100%);
            display: grid;
            grid-template-columns: .75fr 1.25fr;
            overflow: hidden;
            border: 1px solid var(--error-line);
            border-radius: 24px;
            background: #fff;
            box-shadow: 0 18px 50px rgba(6, 31, 68, .10);
        }
        .visual {
            display: grid;
            place-items: center;
            padding: 42px 24px;
            background: var(--error-navy);
            color: #fff;
            text-align: center;
        }
        .code {
            color: #fff;
            font-family: var(--np-font-heading, "Expose", ui-sans-serif, system-ui, sans-serif);
            font-size: 110px;
            font-weight: var(--np-font-weight-black, 900);
            line-height: .85;
        }
        .status {
            margin-top: 15px;
            color: #fff;
            font-size: var(--np-body-2-size, 14px);
            font-weight: var(--np-font-weight-bold, 700);
            line-height: 1;
        }
        .content { padding: 45px; }
        .eyebrow {
            color: var(--error-orange);
            font-size: var(--np-tag-size, 16px);
            font-weight: var(--np-tag-weight, 500);
            line-height: var(--np-tag-line-height, 1.4);
        }
        .content h1 {
            margin: 10px 0 14px;
            color: var(--error-ink);
            font-family: var(--np-font-heading, "Expose", ui-sans-serif, system-ui, sans-serif);
            font-size: var(--np-title-1-size, 48px);
            font-weight: var(--np-title-1-weight, 900);
            line-height: var(--np-title-1-line-height, 1.15);
        }
        .content p { margin: 0; color: var(--error-muted); line-height: var(--np-body-1-line-height, 1.4); }
        .actions { display: flex; flex-wrap: wrap; gap: 11px; margin-top: 26px; }
        .btn {
            min-height: var(--np-button-height, 44px);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--np-button-gap, 8px);
            border: var(--np-button-border-width, 1px) solid var(--error-line);
            border-radius: var(--np-button-radius, .4rem);
            padding: 0 var(--np-button-padding-x, 18px);
            color: var(--error-ink);
            background: #fff;
            font-family: var(--np-font-body, "Expose", ui-sans-serif, system-ui, sans-serif);
            font-size: var(--np-cta-all-caps-regular-size, 16px);
            font-weight: var(--np-cta-all-caps-regular-weight, 700);
            line-height: 1;
            text-decoration: none;
        }
        .btn:hover { transform: translateY(var(--np-button-hover-translate, -1px)); }
        .btn:focus-visible {
            outline: var(--np-button-focus-ring-width, 3px) solid var(--np-button-focus-ring-color, rgba(207, 93, 56, .38));
            outline-offset: var(--np-button-focus-ring-offset, 3px);
        }
        .btn-primary { border-color: var(--error-navy); background: var(--error-navy); color: #fff; }
        .btn-outline:hover { border-color: var(--error-orange); background: var(--error-soft); color: var(--error-navy); }
        footer { padding: 20px; color: var(--error-muted); text-align: center; font-size: var(--np-body-2-size, 14px); }
        @media (max-width: 680px) {
            .card { grid-template-columns: 1fr; }
            .visual { padding: 30px; }
            .code { font-size: 78px; }
            .content { padding: 30px 24px; }
            .content h1 { font-size: clamp(34px, 11vw, var(--np-title-1-size, 48px)); }
            .actions { display: grid; }
            .btn { width: 100%; }
            .home { display: none; }
        }
    </style>
</head>
<body>
    <div class="bar">Custom sportswear, team uniforms, and order support across the USA.</div>
    <header class="head">
        <div class="headin">
            <a href="{{ url('/') }}" class="logo" aria-label="{{ config('storefront.name', 'NextPlay Sportswear') }} home"><x-storefront.brand-logo variant="error" /></a>
            <a class="home" href="{{ url('/') }}">Back to Home</a>
        </div>
    </header>
    <main>
        <section class="card" aria-labelledby="error-title">
            <div class="visual"><div><div class="code">{{ $code }}</div><div class="status">{{ $status }}</div></div></div>
            <div class="content">
                <div class="eyebrow">NextPlay support</div>
                <h1 id="error-title">{{ $title }}</h1>
                <p>{{ $message }}</p>
                <div class="actions">
                    <a class="btn btn-primary" href="{{ url('/') }}">Go to Homepage</a>
                    @if($showSupport ?? true)<a class="btn btn-outline" href="{{ url('/contact-us') }}">Contact Support</a>@endif
                    @if($showShop ?? true)<a class="btn btn-outline" href="{{ url('/products') }}">Browse Products</a>@endif
                </div>
            </div>
        </section>
    </main>
    <footer>© {{ date('Y') }} {{ config('storefront.name', 'NextPlay Sportswear') }}. All rights reserved.</footer>
</body>
</html>
