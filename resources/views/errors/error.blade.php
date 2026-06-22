<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#15345d">
    <title>{{ $code }} — {{ $title }} | {{ config('storefront.name', 'NextPlay Sportswear') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Oswald:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root{--navy:#15345d;--dark:#0d2545;--red:#e91d33;--red2:#c9182b;--ink:#111827;--muted:#64748b;--line:#e2e8f0;--soft:#f3f5f7}*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Inter,system-ui,sans-serif;color:var(--ink);background:linear-gradient(145deg,#f6f8fb,#eef2f6);display:grid;grid-template-rows:auto 1fr auto}.bar{background:var(--dark);color:#fff;padding:10px 20px;text-align:center;font-size:13px;font-weight:800}.head{background:#fff;border-bottom:1px solid var(--line)}.headin{width:min(1060px,calc(100% - 32px));margin:auto;display:flex;justify-content:space-between;align-items:center;padding:17px 0}.logo{display:flex;align-items:center;gap:10px;font-family:Oswald,sans-serif;font-size:23px;font-weight:700;text-transform:uppercase}.mark{width:34px;height:34px;border:3px solid var(--red);border-radius:9px;display:grid;place-items:center;color:var(--red)}.logo b{color:var(--red)}.home{border:1px solid #cbd5e1;border-radius:10px;padding:10px 15px;font-weight:800;text-decoration:none;color:var(--ink)}main{display:grid;place-items:center;padding:44px 16px}.card{width:min(880px,100%);display:grid;grid-template-columns:.75fr 1.25fr;background:#fff;border:1px solid var(--line);border-radius:24px;overflow:hidden;box-shadow:0 18px 50px rgba(15,23,42,.1)}.visual{background:var(--navy);color:#fff;display:grid;place-items:center;padding:42px 24px;text-align:center}.code{font-family:Oswald,sans-serif;font-size:110px;line-height:.85;font-weight:700;color:#fff}.status{margin-top:15px;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.16em;color:#fecdd3}.content{padding:45px}.eyebrow{color:var(--red);font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.16em}.content h1{font-family:Oswald,sans-serif;text-transform:uppercase;font-size:42px;line-height:1.02;margin:10px 0 14px}.content p{color:var(--muted);line-height:1.7;margin:0}.actions{display:flex;gap:11px;flex-wrap:wrap;margin-top:26px}.btn{display:inline-flex;align-items:center;justify-content:center;border-radius:10px;padding:12px 17px;font-size:14px;font-weight:800;text-decoration:none;border:1px solid #cbd5e1;color:var(--ink)}.primary{border-color:var(--red);background:var(--red);color:#fff}.primary:hover{background:var(--red2)}footer{text-align:center;color:#64748b;font-size:12px;padding:20px}@media(max-width:680px){.card{grid-template-columns:1fr}.visual{padding:30px}.code{font-size:78px}.content{padding:30px 24px}.content h1{font-size:34px}.actions{display:grid}.btn{width:100%}.home{display:none}}
    </style>
</head>
<body>
    <div class="bar">Custom sportswear, team uniforms, and order support across the USA.</div>
    <header class="head"><div class="headin"><a href="{{ url('/') }}" class="logo" aria-label="NextPlay Sportswear home"><span class="mark">✓</span><span>NextPlay <b>Sportswear</b></span></a><a class="home" href="{{ url('/') }}">Back to Home</a></div></header>
    <main>
        <section class="card" aria-labelledby="error-title">
            <div class="visual"><div><div class="code">{{ $code }}</div><div class="status">{{ $status }}</div></div></div>
            <div class="content">
                <div class="eyebrow">NextPlay support</div>
                <h1 id="error-title">{{ $title }}</h1>
                <p>{{ $message }}</p>
                <div class="actions">
                    <a class="btn primary" href="{{ url('/') }}">Go to Homepage</a>
                    @if($showSupport ?? true)<a class="btn" href="{{ url('/contact-us') }}">Contact Support</a>@endif
                    @if($showShop ?? true)<a class="btn" href="{{ url('/products') }}">Browse Products</a>@endif
                </div>
            </div>
        </section>
    </main>
    <footer>© {{ date('Y') }} {{ config('storefront.name', 'NextPlay Sportswear') }}. All rights reserved.</footer>
</body>
</html>
