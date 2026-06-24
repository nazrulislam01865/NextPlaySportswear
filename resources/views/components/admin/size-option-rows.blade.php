@props(['name' => 'sizes', 'sizes' => []])

<div x-data="adminSizeOptionRows(@js(old($name, $sizes)))">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="font-black text-brand-ink">Available sizes</h3>
            <p class="mt-1 text-xs leading-5 text-slate-500">Add the exact labels customers will use, such as XS, S, M, L, XL, 2XL.</p>
        </div>
        <button type="button" class="btn btn-white" @click="add()">+ Add Size</button>
    </div>

    <div class="mt-4 space-y-3">
        <template x-for="(size, index) in rows" :key="size.client_key">
            <article class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                <div>
                    <label class="admin-label">Size label
                        <input class="admin-input" :name="`{{ $name }}[${index}][label]`" x-model="size.label" @blur="normalize(size)" placeholder="Example: M" required>
                    </label>
                    <input type="hidden" :name="`{{ $name }}[${index}][code]`" :value="size.code">
                    <input type="hidden" :name="`{{ $name }}[${index}][is_active]`" value="1">
                    <input type="hidden" :name="`{{ $name }}[${index}][sort_order]`" :value="index">
                </div>
                <button type="button" class="btn btn-white text-red-700" @click="remove(index)" :disabled="rows.length === 1">Remove</button>
            </article>
        </template>
    </div>
</div>
