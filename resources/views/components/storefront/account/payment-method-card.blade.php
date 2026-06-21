@props(['paymentMethod'])

<article class="group relative overflow-hidden rounded-[28px] border border-slate-200 bg-white p-5 shadow-card transition hover:-translate-y-0.5 hover:border-brand-red/30 hover:shadow-hero">
    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-navy via-brand-blue to-brand-red"></div>

    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-brand-navy px-3 py-1 text-xs font-black uppercase tracking-wide text-white">{{ $paymentMethod->brand }}</span>
                @if ($paymentMethod->is_default)
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black uppercase tracking-wide text-emerald-700">Default</span>
                @endif
            </div>
            <h3 class="mt-4 text-xl font-black text-brand-ink">•••• •••• •••• {{ $paymentMethod->last_four }}</h3>
            <p class="mt-1 text-sm font-bold text-slate-500">Expires {{ $paymentMethod->expiryLabel() }}</p>
        </div>

        <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-slate-100 text-brand-navy">
            <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="2" y="5" width="20" height="14" rx="2"/>
                <path d="M2 10h20"/>
                <path d="M6 15h4"/>
            </svg>
        </div>
    </div>

    <div class="mt-5 grid gap-3 rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-600">
        <div class="flex items-center justify-between gap-4">
            <span>Nickname</span>
            <span class="text-right font-black text-brand-ink">{{ $paymentMethod->nickname ?: 'Saved card' }}</span>
        </div>
        <div class="flex items-center justify-between gap-4">
            <span>Billing name</span>
            <span class="text-right font-black text-brand-ink">{{ $paymentMethod->billing_name ?: 'Not provided' }}</span>
        </div>
        <div class="flex items-center justify-between gap-4">
            <span>Storage</span>
            <span class="text-right font-black text-emerald-700">Tokenized reference</span>
        </div>
    </div>

    <div class="mt-5 flex flex-col gap-2 sm:flex-row">
        @unless ($paymentMethod->is_default)
            <form method="POST" action="{{ route('account.payment-methods.default', $paymentMethod) }}" class="sm:flex-1">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-white w-full rounded-2xl text-brand-navy">Make Default</button>
            </form>
        @endunless

        <form method="POST" action="{{ route('account.payment-methods.destroy', $paymentMethod) }}" class="sm:flex-1" onsubmit="return confirm('Remove this saved payment method?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-white w-full rounded-2xl border-brand-red/40 text-brand-red hover:bg-brand-red hover:text-white">Remove</button>
        </form>
    </div>
</article>
