@props([
    'title' => 'Detail / Information rows',
    'description' => 'Add the customer-visible product details shown beside the gallery and in Specifications.',
])

<div>
    <label class="block text-sm font-black text-slate-700">{{ $title }}</label>
    <div class="mt-2 overflow-hidden rounded-2xl border border-slate-300 bg-white">
        <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 p-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs leading-5 text-slate-500">{{ $description }}</p>
            <button type="button" class="btn btn-white shrink-0" @click="addSpecification()">+ Add Information Row</button>
        </div>

        <div class="space-y-3 p-4 sm:p-5">
            <template x-for="(spec,index) in specifications" :key="index">
                <article class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50/60 p-3 sm:grid-cols-[minmax(180px,.8fr)_minmax(0,1.4fr)_auto] sm:items-center">
                    <label class="grid gap-2 sm:grid-cols-[72px_minmax(0,1fr)] sm:items-center">
                        <span class="text-xs font-black uppercase tracking-wide text-slate-500">Detail</span>
                        <input class="admin-input mt-0" :name="`specifications[${index}][name]`" x-model="spec.name" placeholder="Fabric, Fit, MOQ, Lead Time...">
                    </label>
                    <label class="grid gap-2 sm:grid-cols-[92px_minmax(0,1fr)] sm:items-center">
                        <span class="text-xs font-black uppercase tracking-wide text-slate-500">Information</span>
                        <input class="admin-input mt-0" :name="`specifications[${index}][value]`" x-model="spec.value" placeholder="Customer-visible information">
                    </label>
                    <button type="button" class="btn btn-white text-red-700" @click="specifications.splice(index,1)" aria-label="Remove information row">Remove</button>
                </article>
            </template>

            <div x-show="specifications.length === 0" class="rounded-2xl border-2 border-dashed border-slate-200 p-8 text-center text-sm text-slate-500">
                No product information row has been added.
            </div>
        </div>
    </div>
</div>
