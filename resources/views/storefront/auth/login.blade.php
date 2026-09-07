<x-layouts.storefront :seo="$seo">

    <x-storefront.auth.shell
        mode="login"
        eyebrow="Secure customer login"
        title="Welcome back"
        subtitle="Sign in to manage your custom jerseys, quote requests, artwork proofs, and order updates."
    >

        <div class="mx-auto max-w-[520px]">


            {{-- Secure Sign In Information --}}
            <div
                class="mb-6 rounded-2xl border border-slate-200 bg-slate-50 p-4"
            >

                <div class="flex gap-3">

                    <span
                        class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-navy text-white"
                    >

                        <svg
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >

                            <path
                                d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"
                            ></path>

                            <path
                                d="m9 12 2 2 4-5"
                            ></path>

                        </svg>

                    </span>


                    <div>

                        <h2 class="text-lg font-black text-brand-ink">
                            Secure sign in
                        </h2>

                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            Your prices, order status, and proof approvals
                            are always handled by the Laravel backend.
                        </p>

                    </div>

                </div>

            </div>



            {{-- Status --}}
            @if (session('status'))

                <div
                    class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700"
                >

                    {{ session('status') }}

                </div>

            @endif



            {{-- Checkout Login Message --}}
            @if (! empty($checkoutIntended))

                <div
                    class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800"
                >

                    Please sign in or create a customer account to continue
                    secure checkout. Your cart will stay saved and will be
                    linked to your account after login.

                </div>

            @endif



            <form
                method="POST"
                action="{{ route('login.store') }}"
                class="grid gap-5"
                novalidate
                data-single-submit
            >

                @csrf


                {{-- Preserve requested redirect --}}
                @if (! empty($redirectUrl))

                    <input
                        type="hidden"
                        name="redirect"
                        value="{{ $redirectUrl }}"
                    >

                @endif



                {{-- Email --}}
                <x-storefront.auth.input
                    name="email"
                    label="Email address"
                    type="email"
                    placeholder="you@example.com"
                    autocomplete="email"
                    required
                />



                {{-- Password --}}
                <div x-data="{ passwordVisible: false }">


                    {{-- Password title / forgot password --}}
                    <div
                        class="mb-2 flex items-center justify-between gap-4"
                    >

                        <label
                            for="password"
                            class="block text-sm font-black text-slate-800"
                        >

                            Password

                            <span class="text-brand-red">
                                *
                            </span>

                        </label>


                        <a
                            href="{{ route('password.request') }}"
                            class="text-sm font-black text-brand-blue transition hover:text-brand-red focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-blue/15"
                        >
                            Forgot password?
                        </a>

                    </div>



                    {{-- Password input wrapper --}}
                    <div class="relative">


                        <input
                            id="password"

                            {{-- Browser-safe default --}}
                            type="password"

                            {{-- Alpine password visibility --}}
                            :type="passwordVisible ? 'text' : 'password'"

                            name="password"

                            placeholder="Enter your password"

                            autocomplete="current-password"

                            required

                            class="h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 pr-12 text-sm font-semibold text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-brand-blue focus:ring-4 focus:ring-brand-blue/10"

                            data-password-input
                        >



                        {{-- Password visibility button --}}
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

                            aria-controls="password"

                            data-password-toggle
                        >


                            {{-- Password hidden: eye icon --}}
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



                            {{-- Password visible: crossed eye --}}
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

                                <path
                                    d="m3 3 18 18"
                                ></path>

                            </svg>

                        </button>

                    </div>



                    {{-- Password validation --}}
                    @error('password')

                        <p
                            class="mt-2 text-sm font-bold text-brand-red"
                        >
                            {{ $message }}
                        </p>

                    @enderror

                </div>



                {{-- Remember / Registration --}}
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >

                    <label
                        class="inline-flex items-center gap-3 text-sm font-bold text-slate-600"
                    >

                        <input
                            type="checkbox"
                            name="remember"
                            value="1"

                            class="h-4 w-4 rounded border-slate-300 text-brand-blue focus:ring-brand-blue"

                            @checked(old('remember'))
                        >

                        Keep me signed in

                    </label>



                    <a
                        href="{{ route(
                            'register',
                            array_filter([
                                'redirect' => $redirectUrl ?? null
                            ])
                        ) }}"

                        class="text-sm font-black text-slate-700 hover:text-brand-red"
                    >

                        Create account to checkout

                    </a>

                </div>



                {{-- Login button --}}
                <button
                    type="submit"
                    class="btn btn-red h-12 w-full rounded-2xl text-base"
                >

                    Sign In Securely

                </button>

            </form>



            {{-- New Customer --}}
            <div
                class="mt-7 rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-center"
            >

                <p class="text-sm font-bold text-slate-600">
                    New to NextPlay Sportswear?
                </p>


                <a
                    href="{{ route(
                        'register',
                        array_filter([
                            'redirect' => $redirectUrl ?? null
                        ])
                    ) }}"

                    class="mt-3 btn btn-white w-full rounded-2xl sm:w-auto"
                >

                    Create Customer Account

                </a>

            </div>

        </div>

    </x-storefront.auth.shell>

</x-layouts.storefront>
