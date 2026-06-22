<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>Admin Login | NextPlay</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 font-sans text-white">
    <main class="grid min-h-screen place-items-center p-5">
        <section class="w-full max-w-md rounded-[28px] border border-white/10 bg-white p-7 text-slate-900 shadow-2xl sm:p-9">
            <div class="mb-8 flex items-center gap-3">
                <span class="grid h-12 w-12 place-items-center rounded-2xl border-2 border-brand-red font-black text-brand-red">✓</span>
                <div><p class="text-xl font-black text-brand-dark">NextPlay</p><p class="text-xs font-black uppercase tracking-[.2em] text-slate-400">Commerce Admin</p></div>
            </div>
            <h1 class="text-3xl font-black tracking-tight text-brand-ink">Administrator login</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">Use an authorized admin account to manage products, categories, SEO, pricing, customization, inventory, and store operations.</p>

            @if(session('status'))<div class="mt-5 rounded-xl bg-emerald-50 p-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="mt-5 rounded-xl bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</div>@endif

            <form method="POST" action="{{ route('admin.login.store') }}" class="mt-7 space-y-4">
                @csrf
                <label class="block text-sm font-black text-slate-700">Email address
                    <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="mt-2 h-12 w-full rounded-xl border border-slate-300 px-4 outline-none focus:border-brand-blue">
                </label>
                <label class="block text-sm font-black text-slate-700">Password
                    <input type="password" name="password" required autocomplete="current-password" class="mt-2 h-12 w-full rounded-xl border border-slate-300 px-4 outline-none focus:border-brand-blue">
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-brand-red"> Keep me signed in on this device</label>
                <button type="submit" class="btn btn-red w-full py-4">Sign in securely</button>
            </form>
            <a href="{{ route('home') }}" class="mt-5 block text-center text-sm font-bold text-brand-blue">← Return to storefront</a>
        </section>
    </main>
</body>
</html>
