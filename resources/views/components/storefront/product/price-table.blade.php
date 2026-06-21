@props([
    'tiers' => [],
])

<section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-card">
    <div class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-5 py-5 sm:px-6">
        <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Bulk pricing</p>
        <h2 class="mt-1 text-3xl font-black tracking-tight text-brand-ink">Price Table</h2>
        <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-600">Estimated pricing is shown for planning. Final pricing can change based on artwork, fabric, printing method, rush production, accessories, and shipping address.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-100 text-slate-700">
                <tr>
                    <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-wide">Quantity</th>
                    <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-wide">Product</th>
                    <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-wide">Shipping</th>
                    <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-wide">Estimated each</th>
                    <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-wide">Order total</th>
                    <th class="px-5 py-4 text-left text-xs font-black uppercase tracking-wide">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @foreach ($tiers as $tier)
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap px-5 py-4 font-black text-brand-ink">{{ $tier['quantity'] }}</td>
                        <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-600">{{ $tier['product_price'] }}</td>
                        <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-600">{{ $tier['shipping'] }}</td>
                        <td class="whitespace-nowrap px-5 py-4 font-black text-brand-red">{{ $tier['estimated_each'] }}</td>
                        <td class="whitespace-nowrap px-5 py-4 font-black text-brand-ink">{{ $tier['estimated_order_total'] ?? 'Quote' }}</td>
                        <td class="min-w-[180px] px-5 py-4 font-medium text-slate-600">{{ $tier['note'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
