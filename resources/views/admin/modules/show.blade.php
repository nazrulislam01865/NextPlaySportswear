<x-layouts.admin title="Unavailable" eyebrow="Launch configuration" subtitle="This admin section is not enabled.">
    <section class="rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-card">
        <h2 class="text-xl font-black text-brand-ink">Section unavailable</h2>
        <p class="mt-2 text-sm text-slate-500">Use the available navigation links to manage the live store features.</p>
        <a class="btn btn-red mt-5" href="{{ route('admin.dashboard') }}">Back to Dashboard</a>
    </section>
</x-layouts.admin>
