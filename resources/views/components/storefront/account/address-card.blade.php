@props(['address'])

<article class="group relative overflow-hidden rounded-[28px] border border-slate-200 bg-white p-5 shadow-card transition hover:-translate-y-0.5 hover:border-brand-red/30 hover:shadow-hero">
    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-red via-brand-blue to-brand-navy"></div>

    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-brand-navy px-3 py-1 text-xs font-black uppercase tracking-wide text-white">
                    {{ $address->typeLabel() }}
                </span>
                @if ($address->is_default)
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black uppercase tracking-wide text-emerald-700">Default</span>
                @endif
            </div>

            <h3 class="mt-4 text-lg font-black text-brand-ink">{{ $address->formattedName() }}</h3>
            @if ($address->company_name)
                <p class="mt-1 text-sm font-bold text-slate-500">{{ $address->company_name }}</p>
            @endif
        </div>

        <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-slate-100 text-brand-navy">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
        </div>
    </div>

    <div class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm font-semibold leading-6 text-slate-600">
        <p>{{ $address->address_line_1 }}</p>
        @if ($address->address_line_2)
            <p>{{ $address->address_line_2 }}</p>
        @endif
        <p>{{ $address->city }}{{ $address->state ? ', ' . $address->state : '' }} {{ $address->postal_code }}</p>
        <p>{{ $address->country }}</p>
        @if ($address->phone)
            <p class="mt-2 text-brand-ink">Phone: {{ $address->phone }}</p>
        @endif
        @if ($address->email)
            <p class="text-brand-ink">Email: {{ $address->email }}</p>
        @endif
    </div>

    <div class="mt-5 flex flex-col gap-2 sm:flex-row">
        @unless ($address->is_default)
            <form method="POST" action="{{ route('account.addresses.default', $address) }}" class="sm:flex-1">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-white w-full rounded-2xl text-brand-navy">Make Default</button>
            </form>
        @endunless

        <form method="POST" action="{{ route('account.addresses.destroy', $address) }}" class="sm:flex-1" onsubmit="return confirm('Remove this saved address?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-white w-full rounded-2xl border-brand-red/40 text-brand-red hover:bg-brand-red hover:text-white">Remove</button>
        </form>
    </div>
</article>
