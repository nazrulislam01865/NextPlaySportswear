<div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4" data-customization-extra-charge>
    <div class="grid gap-4 lg:grid-cols-2">
        <label class="block">
            <span class="text-sm font-black text-slate-700">Additional charge</span>
            <div class="mt-2 flex overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm focus-within:border-brand-blue focus-within:ring-2 focus-within:ring-brand-blue/10">
                <span class="grid min-w-11 place-items-center border-r border-slate-200 bg-slate-50 px-3 text-sm font-black text-slate-500">$</span>
                <input
                    class="min-w-0 flex-1 border-0 bg-white px-3 py-2.5 text-sm font-bold text-slate-800 outline-none focus:ring-0"
                    type="number"
                    min="0"
                    max="999999999.99"
                    step="0.01"
                    inputmode="decimal"
                    :id="`option-charge-${gIndex}-${vIndex}`"
                    :name="`option_groups[${gIndex}][values][${vIndex}][price_adjustment]`"
                    x-model="value.price_adjustment"
                    placeholder="0.00"
                >
            </div>
        </label>

        <label class="block">
            <span class="text-sm font-black text-slate-700">Charge basis</span>
            <select
                class="admin-input !mt-2"
                :id="`option-charge-basis-${gIndex}-${vIndex}`"
                :name="`option_groups[${gIndex}][values][${vIndex}][charge_type]`"
                x-model="value.charge_type"
            >
                <option value="included">Included / no extra charge</option>
                <option value="per_unit">Per piece</option>
                <option value="fixed_order">Fixed per order</option>
            </select>
        </label>
    </div>

    <p class="mt-3 text-xs font-semibold leading-5 text-slate-500">
        Set the amount to 0.00 when there is no surcharge. Per piece is added for every ordered unit; fixed per order is added once regardless of quantity.
    </p>
</div>
