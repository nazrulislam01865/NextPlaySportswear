@props(['category', 'filters', 'options', 'idPrefix' => 'category-filter'])

<form method="GET" action="{{ $category['url'] }}" class="space-y-6" aria-label="Filter products">
    <div>
        <label for="{{ $idPrefix }}-search" class="text-sm font-extrabold text-brand-ink">Search this category</label>
        <input
            id="{{ $idPrefix }}-search"
            class="mt-2 h-11 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-blue"
            type="search"
            name="q"
            value="{{ $filters['q'] }}"
            maxlength="100"
            placeholder="Search products..."
        >
    </div>

    @if ($options['subcategories'] !== [])
        <fieldset class="border-t border-slate-200 pt-5">
            <legend class="text-sm font-extrabold text-brand-ink">Shop within</legend>
            <div class="mt-3 space-y-3">
                @foreach ($options['subcategories'] as $option)
                    @php($fieldId = $idPrefix.'-subcategory-'.$option['id'])
                    <label for="{{ $fieldId }}" class="flex cursor-pointer items-center justify-between gap-3 text-sm text-slate-600">
                        <span class="flex items-center gap-3">
                            <input
                                id="{{ $fieldId }}"
                                type="checkbox"
                                name="subcategory[]"
                                value="{{ $option['id'] }}"
                                @checked(in_array((int) $option['id'], $filters['subcategory'], true))
                                class="h-4 w-4 rounded border-slate-300 text-brand-red"
                            >
                            <span>{{ $option['label'] }}</span>
                        </span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold">{{ $option['count'] }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif

    @foreach ($options['attributes'] as $attribute)
        <details class="group border-t border-slate-200 pt-5" @if($attribute['is_expanded']) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-extrabold text-brand-ink">
                <span>{{ $attribute['name'] }}</span>
                <span class="text-lg text-slate-400 transition group-open:rotate-45" aria-hidden="true">+</span>
            </summary>
            <div class="mt-3 space-y-3">
                @foreach ($attribute['values'] as $value)
                    @php($fieldId = $idPrefix.'-'.$attribute['slug'].'-'.$value['id'])
                    <label for="{{ $fieldId }}" class="flex cursor-pointer items-center justify-between gap-3 text-sm text-slate-600">
                        <span class="flex min-w-0 items-center gap-3">
                            <input
                                id="{{ $fieldId }}"
                                type="checkbox"
                                name="attributes[{{ $attribute['slug'] }}][]"
                                value="{{ $value['slug'] }}"
                                @checked(in_array($value['slug'], $filters['attributes'][$attribute['slug']] ?? [], true))
                                class="h-4 w-4 rounded border-slate-300 text-brand-red"
                            >
                            @if ($attribute['display_type'] === 'color' && $value['color_hex'])
                                <span class="h-6 w-6 shrink-0 rounded-full border border-slate-300" style="background: {{ $value['color_hex'] }}"></span>
                            @elseif ($attribute['display_type'] === 'image' && $value['image'])
                                <img src="{{ $value['image'] }}" alt="" class="h-8 w-10 rounded object-cover" loading="lazy">
                            @endif
                            <span class="truncate">{{ $value['label'] }}</span>
                        </span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold">{{ $value['count'] }}</span>
                    </label>
                @endforeach
            </div>
        </details>
    @endforeach

    <fieldset class="border-t border-slate-200 pt-5">
        <legend class="text-sm font-extrabold text-brand-ink">Starting price</legend>
        <div class="mt-3 grid grid-cols-2 gap-3">
            <label class="text-xs font-bold text-slate-500">
                Minimum
                <input class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3" type="number" name="min_price" value="{{ $filters['min_price'] }}" min="0" step="1">
            </label>
            <label class="text-xs font-bold text-slate-500">
                Maximum
                <input class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3" type="number" name="max_price" value="{{ $filters['max_price'] }}" min="0" step="1" placeholder="{{ $options['price_ceiling'] }}">
            </label>
        </div>
    </fieldset>

    <fieldset class="border-t border-slate-200 pt-5">
        <legend class="text-sm font-extrabold text-brand-ink">Availability</legend>
        <div class="mt-3 space-y-3">
            <label class="flex items-center gap-3 text-sm">
                <input type="hidden" name="in_stock" value="0">
                <input type="checkbox" name="in_stock" value="1" @checked($filters['in_stock'])>
                In stock or backorderable
            </label>
            <label class="flex items-center gap-3 text-sm">
                <input type="hidden" name="customizable" value="0">
                <input type="checkbox" name="customizable" value="1" @checked($filters['customizable'])>
                Customizable products
            </label>
        </div>
    </fieldset>

    <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
    <div class="grid grid-cols-2 gap-3 border-t border-slate-200 pt-5">
        <a href="{{ $category['url'] }}" class="btn btn-white px-3">Reset</a>
        <button class="btn btn-red px-3">Apply Filters</button>
    </div>
</form>
