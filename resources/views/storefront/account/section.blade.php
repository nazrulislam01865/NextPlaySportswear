<x-layouts.storefront :seo="$seo ?? []">
    <section class="mx-auto max-w-3xl px-4 py-16 text-center sm:px-6 lg:px-8">
        <h1 class="text-3xl font-black text-brand-dark">Account section unavailable</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">This section is not part of the current account experience.</p>
        <a class="btn btn-red mt-6" href="{{ route('account.dashboard') }}">Back to My Account</a>
    </section>
</x-layouts.storefront>
