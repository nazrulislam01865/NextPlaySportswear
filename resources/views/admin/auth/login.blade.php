<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">

    <title>Admin Login | NextPlay</title>

    @vite([
        'resources/css/admin.css',
        'resources/js/admin.js'
    ])
</head>

<body class="admin-auth-ui min-h-screen bg-slate-950 font-sans text-white">

    <main class="grid min-h-screen place-items-center p-5">

        <section
            class="w-full max-w-md rounded-[28px] border border-white/10 bg-white p-7 text-slate-900 shadow-2xl sm:p-9"
        >

            {{-- Admin branding --}}
            <div class="mb-8 flex items-center gap-3">

                <span
                    class="grid h-12 w-12 place-items-center rounded-2xl border-2 border-brand-red font-black text-brand-red"
                >
                    ✓
                </span>

                <div>
                    <p class="text-xl font-black text-brand-dark">
                        NextPlay
                    </p>

                    <p class="text-xs font-black uppercase tracking-[.2em] text-slate-400">
                        Commerce Admin
                    </p>
                </div>

            </div>

            <h1 class="text-3xl font-black tracking-tight text-brand-ink">
                Administrator login
            </h1>

            <p class="mt-2 text-sm leading-6 text-slate-500">
                Use an authorized admin account to manage products,
                categories, SEO, pricing, customization, inventory,
                and store operations.
            </p>


            {{-- Success / status message --}}
            @if(session('status'))
                <div
                    class="mt-5 rounded-xl bg-emerald-50 p-3 text-sm font-bold text-emerald-800"
                >
                    {{ session('status') }}
                </div>
            @endif


            {{-- Validation / login errors --}}
            @if($errors->any())
                <div
                    class="mt-5 rounded-xl bg-red-50 p-3 text-sm text-red-800"
                >
                    {{ $errors->first() }}
                </div>
            @endif


            <form
                method="POST"
                action="{{ route('admin.login.store') }}"
                class="mt-7 space-y-4"
                data-admin-login-form
            >

                @csrf


                {{-- Email --}}
                <label class="block text-sm font-black text-slate-700">

                    Email address

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="username"
                        class="mt-2 h-12 w-full rounded-xl border border-slate-300 px-4 outline-none focus:border-brand-blue"
                    >

                </label>


                {{-- Password --}}
                <div x-data="{ passwordVisible: false }">

                    <label
                        for="admin-password"
                        class="block text-sm font-black text-slate-700"
                    >
                        Password
                    </label>


                    <div class="relative mt-2">

                        <input
                            id="admin-password"

                            {{-- Important fallback --}}
                            type="password"

                            {{-- Alpine changes password -> text --}}
                            :type="passwordVisible ? 'text' : 'password'"

                            name="password"
                            required
                            autocomplete="current-password"

                            class="h-12 w-full rounded-xl border border-slate-300 px-4 pr-12 outline-none focus:border-brand-blue"

                            data-password-input
                        >


                        {{-- Password show/hide button --}}
                        <button
                            type="button"

                            class="absolute inset-y-0 right-0 grid w-12 cursor-pointer place-items-center text-slate-500 transition hover:text-brand-blue focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-blue/30"

                            @click="passwordVisible = ! passwordVisible"

                            aria-label="Show password"

                            :aria-label="passwordVisible
                                ? 'Hide password'
                                : 'Show password'"

                            aria-pressed="false"

                            :aria-pressed="passwordVisible.toString()"

                            aria-controls="admin-password"

                            data-password-toggle
                        >

                            {{-- Eye icon --}}
                            <svg
                                x-show="! passwordVisible"
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path
                                    d="M2.06 12.35a1 1 0 0 1 0-.7C3.72 7.73 7.16 5 12 5s8.28 2.73 9.94 6.65a1 1 0 0 1 0 .7C20.28 16.27 16.84 19 12 19s-8.28-2.73-9.94-6.65Z"
                                ></path>

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="3"
                                ></circle>
                            </svg>


                            {{-- Eye-off icon --}}
                            <svg
                                x-cloak
                                x-show="passwordVisible"
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >

                                <path
                                    d="M10.73 5.08A10.43 10.43 0 0 1 12 5c4.84 0 8.28 2.73 9.94 6.65a1 1 0 0 1 0 .7 11.05 11.05 0 0 1-1.43 2.49"
                                ></path>

                                <path
                                    d="M6.61 6.61A11.03 11.03 0 0 0 2.06 11.65a1 1 0 0 0 0 .7C3.72 16.27 7.16 19 12 19a10.8 10.8 0 0 0 5.39-1.39"
                                ></path>

                                <path d="m3 3 18 18"></path>

                            </svg>

                        </button>

                    </div>

                </div>


                {{-- Remember --}}
                <label class="flex items-center gap-2 text-sm text-slate-600">

                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                        class="rounded border-slate-300 text-brand-red"
                    >

                    Keep me signed in on this device

                </label>


                {{-- Submit --}}
                <button
                    type="submit"
                    class="btn btn-red w-full py-4"
                    data-admin-login-submit
                    data-submitting-label="Signing in…"
                >
                    Sign in securely
                </button>

            </form>


            <a
                href="{{ route('home') }}"
                class="mt-5 block text-center text-sm font-bold text-brand-blue"
            >
                ← Return to storefront
            </a>

        </section>

    </main>


    {{-- Existing duplicate-submit protection --}}
    <script>
        (() => {

            const form = document.querySelector(
                '[data-admin-login-form]'
            );

            const button = form?.querySelector(
                '[data-admin-login-submit]'
            );


            if (!form || !button) {
                return;
            }


            form.addEventListener('submit', (event) => {

                /*
                 * Prevent accidental multiple login requests.
                 */
                if (form.dataset.submitting === '1') {

                    event.preventDefault();

                    return;
                }


                form.dataset.submitting = '1';


                button.disabled = true;

                button.setAttribute(
                    'aria-disabled',
                    'true'
                );


                button.textContent =
                    button.dataset.submittingLabel
                    || 'Signing in…';

            });

        })();
    </script>

</body>
</html>
