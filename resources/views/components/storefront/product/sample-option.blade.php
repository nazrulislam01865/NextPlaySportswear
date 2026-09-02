@props(['sample', 'stepNumber'])

<section class="rounded-[28px] border border-slate-200 bg-white shadow-card" id="sample-order">
    <div class="flex items-start gap-4 border-b border-slate-200 bg-gradient-to-r from-white to-red-50 p-5 sm:p-6">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-dark font-black text-white">{{ $stepNumber }}</span>
        <div class="min-w-0">
            <h3 class="text-xl font-black leading-tight text-brand-ink sm:text-2xl">Sample Available</h3>
            <p class="mt-1 text-sm leading-6 text-slate-500">Want to check the product before placing your main order? Add one sample to this order.</p>
        </div>
    </div>

    <div class="p-4 sm:p-5">
        <label
            class="group flex cursor-pointer flex-col gap-4 rounded-2xl border-2 p-4 transition sm:flex-row sm:items-center sm:justify-between sm:p-5"
            :class="sampleRequested ? 'border-brand-blue bg-blue-50/60 shadow-[0_0_0_1px_#2563eb]' : 'border-slate-200 bg-white hover:border-brand-blue'"
        >
            <span class="flex min-w-0 items-start gap-3">
                <span class="mt-0.5 flex shrink-0 items-center justify-center">
                    <input
                        type="checkbox"
                        class="h-5 w-5 cursor-pointer rounded border-slate-300 text-brand-blue focus:ring-brand-blue"
                        :checked="sampleRequested"
                        @change="toggleSample($event.target.checked)"
                        aria-describedby="sample-order-help"
                    >
                </span>

                <span class="min-w-0">
                    <span class="flex flex-wrap items-center gap-2">
                        <strong class="block text-base text-brand-ink">Add a sample to my order</strong>
                        <span
                            x-show="sampleRequested"
                            x-cloak
                            class="rounded-full bg-brand-blue px-2.5 py-1 text-[10px] font-black uppercase tracking-[.12em] text-white"
                        >Selected</span>
                    </span>
                    <small id="sample-order-help" class="mt-1 block text-xs leading-5 text-slate-500">One sample request will be included with this configured product. The fee is added once per order.</small>
                </span>
            </span>

            <span class="flex shrink-0 items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 sm:block sm:text-right sm:shadow-sm">
                <span class="block text-left sm:text-right">
                    <small class="block text-[10px] font-black uppercase tracking-[.12em] text-slate-400">One-time sample fee</small>
                    <strong class="mt-1 block text-lg font-black text-brand-red" x-text="sampleChargeLabel()"></strong>
                </span>
                <span x-show="sampleRequested" x-cloak class="text-xs font-black text-brand-blue sm:mt-1 sm:block">Added to total</span>
            </span>
        </label>
    </div>
</section>
