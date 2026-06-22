<x-layouts.admin :title="$title">
    <section class="rounded-3xl border border-slate-200 bg-white p-7 shadow-card">
        <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-black uppercase tracking-[.12em] text-brand-blue">E-commerce admin module</span>
        <h2 class="mt-4 text-3xl font-black text-brand-ink">{{ $title }}</h2>
        <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">{{ $description }}</p>
        <div class="mt-7 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-900">
            The navigation and access control for this module are ready. Product and category management are fully implemented in this release; this operational module can now be connected to its dedicated database workflow without changing the admin layout.
        </div>
    </section>
</x-layouts.admin>
