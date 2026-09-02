@props([
    'typeLabel',
    'typeValue',
    'options',
    'filters' => [],
    'storeRoute',
    'editRouteName',
    'destroyRouteName',
    'placeholder',
    'imageTitle',
    'imageHint',
    'imageCta',
    'isColor' => false,
    'showsDescription' => false,
    'descriptionPlaceholder' => 'Example: Lightweight dry-fit mesh with breathable texture and quick-dry finish.',
    'descriptionHint' => 'Add only useful fabric information. This helps the admin and customer understand the material quickly.',
])

@php
    $columnCount = 4 + ($isColor ? 1 : 0) + ($showsDescription ? 1 : 0);
@endphp

<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_390px]">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <div class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div>
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-blue">Already Added</p>
                <h2 class="mt-1 text-2xl font-black text-brand-ink">{{ $typeLabel }} List</h2>
            </div>
            <form method="GET" class="flex w-full gap-2 sm:w-auto">
                <input
                    class="admin-input mt-0 min-w-0 sm:w-72"
                    name="q"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="Search {{ strtolower($typeLabel) }}"
                >
                <button class="btn btn-white shrink-0">Search</button>
            </form>
        </div>

        <div class="admin-table-scroll" tabindex="0" aria-label="{{ $typeLabel }} table">
            <table class="admin-table min-w-[760px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Name</th>
                        @if($isColor)
                            <th class="px-5 py-4">Color Value</th>
                        @endif
                        @if($showsDescription)
                            <th class="px-5 py-4">Details</th>
                        @endif
                        <th class="px-5 py-4">Image</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($options as $item)
                        <tr>
                            <td class="px-5 py-4">
                                <strong class="text-brand-ink">{{ $item->name }}</strong>
                            </td>
                            @if($isColor)
                                <td class="px-5 py-4">
                                    @if($item->color_hex)
                                        <div class="flex items-center gap-2 font-bold text-slate-700">
                                            <span
                                                class="h-6 w-6 rounded-full border border-slate-300 shadow-sm"
                                                style="background: {{ $item->color_hex }}"
                                            ></span>
                                            <span>{{ $item->color_hex }}</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            @endif
                            @if($showsDescription)
                                <td class="max-w-[280px] px-5 py-4">
                                    @if(filled($item->description))
                                        <p class="max-h-12 overflow-hidden text-sm font-semibold leading-6 text-slate-600">{{ $item->description }}</p>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            @endif
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                                        @if($item->primaryImage?->publicUrl())
                                            <img
                                                class="h-full w-full object-cover"
                                                src="{{ $item->primaryImage->publicUrl() }}"
                                                alt="{{ $item->primaryImage->name }}"
                                            >
                                        @else
                                            <span class="text-[10px] font-black text-slate-400">None</span>
                                        @endif
                                    </div>
                                    @if($item->images_count > 1)
                                        <span class="text-xs font-semibold text-slate-500">+{{ $item->images_count - 1 }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span @class([
                                    'inline-flex rounded-full px-3 py-1 text-xs font-black',
                                    'bg-emerald-50 text-emerald-700' => $item->is_active,
                                    'bg-slate-100 text-slate-500' => ! $item->is_active,
                                ])>
                                    {{ $item->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="admin-row-actions">
                                    <a
                                        class="admin-row-action border-slate-200"
                                        href="{{ route($editRouteName, $item) }}?return_type={{ $typeValue }}"
                                    >
                                        Edit
                                    </a>
                                    <form
                                        method="POST"
                                        action="{{ route($destroyRouteName, $item) }}"
                                        onsubmit="return confirm('Delete this {{ strtolower($typeLabel) }} option?');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button class="admin-row-action border-red-200 text-red-700 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $columnCount }}" class="px-5 py-14 text-center">
                                <p class="font-black text-brand-ink">No {{ strtolower($typeLabel) }} option has been added.</p>
                                <p class="mt-1 text-sm font-semibold text-slate-500">Use the form beside this table to add the first item.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 p-5">{{ $options->links('pagination.nextplay') }}</div>
    </section>

    <aside class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card sm:p-6 xl:sticky xl:top-36 xl:self-start">
        <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Add New Item</p>
        <h2 class="mt-1 text-2xl font-black text-brand-ink">Add {{ $typeLabel }}</h2>
        <p class="mt-2 text-sm font-semibold leading-6 text-slate-500">Only the necessary fields are shown for this option type.</p>

        <form
            class="mt-6 space-y-5"
            method="POST"
            action="{{ $storeRoute }}"
            enctype="multipart/form-data"
            x-data="{
                optionName: @js(old('name', '')),
                colorValue: @js(old('color_hex', '#111827')),
                imageFileName: '',
                imagePreview: '',
                handleImageSelection(event) {
                    const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
                    this.imageFileName = file ? file.name : '';
                    if (this.imagePreview) URL.revokeObjectURL(this.imagePreview);
                    this.imagePreview = file ? URL.createObjectURL(file) : '';
                }
            }"
        >
            @csrf
            <input type="hidden" name="_return_to_type" value="1">
            <input type="hidden" name="type" value="{{ $typeValue }}">

            <label class="admin-label">
                {{ $isColor ? 'Color name' : $typeLabel.' name' }}
                <input
                    class="admin-input"
                    name="name"
                    x-model="optionName"
                    value="{{ old('name') }}"
                    maxlength="160"
                    placeholder="{{ $placeholder }}"
                    required
                >
            </label>

            @if($isColor)
                <label class="admin-label">
                    Color value
                    <div class="mt-2 flex items-center gap-3">
                        <input
                            class="h-12 w-16 shrink-0 cursor-pointer rounded-xl border border-slate-300 bg-white p-1"
                            type="color"
                            x-model="colorValue"
                            @input="colorValue = $event.target.value.toUpperCase()"
                        >
                        <input
                            class="admin-input mt-0"
                            name="color_hex"
                            x-model="colorValue"
                            maxlength="7"
                            placeholder="#111827"
                            required
                        >
                    </div>
                </label>
            @endif

            @if($showsDescription)
                <label class="admin-label">
                    Details
                    <textarea
                        class="admin-textarea min-h-28 resize-y"
                        name="description"
                        maxlength="2000"
                        placeholder="{{ $descriptionPlaceholder }}"
                    >{{ old('description') }}</textarea>
                    <span class="mt-1 block text-xs font-semibold text-slate-500">
                        {{ $descriptionHint }}
                    </span>
                </label>
            @endif

            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-4">
                <div class="flex items-start gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white text-lg shadow-sm">🖼</span>
                    <div class="min-w-0">
                        <p class="text-sm font-black text-brand-ink">{{ $imageTitle }}</p>
                        <p class="mt-1 text-xs font-semibold leading-5 text-slate-500">{{ $imageHint }}</p>
                    </div>
                </div>

                <label class="mt-4 block cursor-pointer rounded-2xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-brand-blue hover:bg-blue-50/30">
                    <input
                        class="sr-only"
                        type="file"
                        name="images[0][image_file]"
                        accept="image/jpeg,image/png,image/webp,image/avif"
                        @change="handleImageSelection"
                    >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="grid h-20 w-full shrink-0 place-items-center overflow-hidden rounded-xl border border-slate-200 bg-slate-100 sm:w-24">
                            <template x-if="imagePreview">
                                <img class="h-full w-full object-cover" :src="imagePreview" alt="Selected option image preview">
                            </template>
                            <template x-if="! imagePreview">
                                <span class="text-2xl text-slate-400">+</span>
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="inline-flex min-h-10 items-center rounded-xl bg-brand-blue px-4 text-sm font-black text-white">{{ $imageCta }}</span>
                            <p class="mt-2 truncate text-xs font-bold text-slate-500" x-text="imageFileName || 'JPG, PNG, WEBP or AVIF up to 5MB'"></p>
                        </div>
                    </div>
                </label>

                <input type="hidden" name="images[0][existing_id]" value="">
                <input type="hidden" name="images[0][name]" value="">
                <input type="hidden" name="images[0][image_url]" value="">
                <input type="hidden" name="images[0][is_primary]" value="1">
                <input type="hidden" name="images[0][sort_order]" value="0">
            </div>

            <button class="btn btn-red w-full">+ Add {{ $typeLabel }}</button>
        </form>
    </aside>
</div>
