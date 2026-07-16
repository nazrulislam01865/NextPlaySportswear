@props([
    'filters' => [],
    'selectedCategoryIds' => [],
    'query' => '',
    'tag' => '',
    'idPrefix' => 'product-category-filter',
    'heading' => 'Filters',
    'subheading' => 'Search and select categories to narrow the product list.',
])

@php
    $selectedCategoryIds = collect($selectedCategoryIds)
        ->map(fn ($id) => (int) $id)
        ->filter(fn (int $id): bool => $id > 0)
        ->values()
        ->all();

    $selectedCount = count($selectedCategoryIds);

    $iconForCategory = function (string $label): string {
        $name = strtolower($label);

        if (str_contains($name, 'bag')) {
            return '<path d="M6 8h12l-1 11H7L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>';
        }

        if (str_contains($name, 'accessor') || str_contains($name, 'gear')) {
            return '<path d="M5 9h14v10H5z"/><path d="M8 9V7a4 4 0 0 1 8 0v2"/>';
        }

        if (str_contains($name, 'sport') || str_contains($name, 'event')) {
            return '<path d="M4 5l8 4l8-4v12l-8 4l-8-4V5Z"/><path d="M12 9v12"/><path d="M4 5l8 4l8-4"/>';
        }

        if (str_contains($name, 'cap') || str_contains($name, 'head')) {
            return '<path d="M4 15c0-4.5 3.5-8 8-8s8 3.5 8 8"/><path d="M2 15h20"/><path d="M12 7V4"/>';
        }

        return '<path d="M9 4h6l3 3l-2 2v11H8V9L6 7l3-3Z"/><path d="M9 4c.7 1.2 1.7 1.8 3 1.8S14.3 5.2 15 4"/>';
    };
@endphp

@once
    <style>
        .np-filter-shell,
        .np-filter-drawer {
            min-width: 0;
        }

        .np-filter-shell {
            height: min(760px, calc(100vh - 8rem));
            border: 1px solid #dbe5f1;
            background: #ffffff;
            border-radius: 22px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, .08);
        }

        .np-product-filter-form {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            gap: 16px;
            min-height: 0;
            width: 100%;
        }

        .np-category-filter-card {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
            width: 100%;
        }

        .np-filter-top {
            flex: 0 0 auto;
        }

        .np-filter-searchbox {
            position: relative;
        }

        .np-filter-searchbox svg {
            position: absolute;
            left: 13px;
            top: 50%;
            width: 18px;
            height: 18px;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .np-filter-category-search {
            width: 100%;
            height: 48px;
            border: 1px solid #d5dde8;
            border-radius: 14px;
            background: #ffffff;
            color: #111827;
            font-size: 14px;
            font-weight: 650;
            outline: none;
            padding: 0 14px 0 43px;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .np-filter-category-search::placeholder {
            color: #7b8493;
            font-weight: 600;
        }

        .np-filter-category-search:focus {
            border-color: #e91d33;
            box-shadow: 0 0 0 4px rgba(233, 29, 51, .09);
        }

        .np-filter-heading-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: 18px;
        }

        .np-filter-section-label {
            margin: 0;
            color: #97a1b1;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .12em;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .np-filter-active-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 26px;
            min-height: 26px;
            border-radius: 999px;
            background: #fff1f2;
            color: #e91d33;
            font-size: 12px;
            font-weight: 900;
            line-height: 1;
            padding: 0 9px;
            white-space: nowrap;
        }

        .np-filter-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            margin-top: 10px;
            padding-right: 4px;
            overscroll-behavior: contain;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        .np-filter-scroll::-webkit-scrollbar {
            width: 7px;
        }

        .np-filter-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }

        .np-filter-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .np-category-list {
            display: flex;
            flex-direction: column;
        }

        .np-category-group {
            border-bottom: 1px solid #e2e8f0;
        }

        .np-category-group:last-child {
            border-bottom: none;
        }

        .np-category-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            min-height: 52px;
            cursor: pointer;
            list-style: none;
            padding: 10px 0;
            user-select: none;
        }

        .np-category-summary::-webkit-details-marker {
            display: none;
        }

        .np-parent-filter-option,
        .np-child-filter-option {
            cursor: pointer;
        }

        .np-parent-filter-option {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 0;
            flex: 1 1 auto;
            border-radius: 12px;
            padding: 7px 8px;
            transition: background .15s ease, color .15s ease;
        }

        .np-parent-filter-option:hover {
            background: #f8fafc;
        }

        .np-category-icon {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            color: #111827;
            transition: color .15s ease, transform .15s ease;
        }

        .np-category-icon svg {
            width: 21px;
            height: 21px;
        }

        .np-filter-checkbox {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        .np-parent-title {
            min-width: 0;
            color: #111827;
            font-size: 15px;
            font-weight: 900;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .np-parent-meta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex: 0 0 auto;
        }

        .np-count-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 30px;
            min-height: 26px;
            border-radius: 999px;
            background: #f8fafc;
            color: #526071;
            font-size: 12px;
            font-weight: 900;
            line-height: 1;
            padding: 0 9px;
        }

        .np-chevron {
            color: #9aa6b7;
            font-size: 18px;
            font-weight: 900;
            line-height: 1;
            transition: transform .15s ease, color .15s ease;
        }

        .np-category-group[open] .np-chevron {
            transform: rotate(180deg);
        }

        .np-child-list {
            display: flex;
            flex-direction: column;
            gap: 2px;
            margin: 0 0 12px 34px;
        }

        .np-child-filter-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            min-height: 36px;
            border: 1px solid transparent;
            border-radius: 11px;
            color: #687386;
            padding: 6px 8px;
            transition: background .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease;
        }

        .np-child-filter-option:hover {
            background: #f8fafc;
            color: #111827;
        }

        .np-child-name {
            min-width: 0;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.3;
            overflow-wrap: anywhere;
        }

        .np-selected-mark {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 999px;
            background: transparent;
            color: transparent;
            transition: background .15s ease, color .15s ease;
        }

        .np-selected-mark svg {
            width: 14px;
            height: 14px;
        }

        .np-category-group.is-selected .np-parent-filter-option,
        .np-category-group:has(> .np-category-summary .np-filter-checkbox:checked) .np-parent-filter-option,
        .np-category-group:has(.np-child-filter-option .np-filter-checkbox:checked) .np-parent-filter-option {
            background: #fff1f2;
        }

        .np-category-group.is-selected .np-category-icon,
        .np-category-group:has(> .np-category-summary .np-filter-checkbox:checked) .np-category-icon,
        .np-category-group:has(.np-child-filter-option .np-filter-checkbox:checked) .np-category-icon {
            color: #e91d33;
            transform: translateY(-1px);
        }

        .np-category-group.is-selected .np-parent-title,
        .np-category-group:has(> .np-category-summary .np-filter-checkbox:checked) .np-parent-title,
        .np-category-group:has(.np-child-filter-option .np-filter-checkbox:checked) .np-parent-title {
            color: #e91d33;
        }

        .np-category-group.is-selected > .np-category-summary .np-parent-meta .np-count-pill,
        .np-category-group:has(> .np-category-summary .np-filter-checkbox:checked) > .np-category-summary .np-parent-meta .np-count-pill,
        .np-category-group:has(.np-child-filter-option .np-filter-checkbox:checked) > .np-category-summary .np-parent-meta .np-count-pill {
            background: #ffe4e8;
            color: #be123c;
        }

        .np-child-filter-option.is-selected,
        .np-child-filter-option:has(.np-filter-checkbox:checked) {
            border-color: rgba(233, 29, 51, .20);
            background: #fff1f2;
            color: #e91d33;
            box-shadow: 0 8px 18px rgba(233, 29, 51, .07);
        }

        .np-child-filter-option.is-selected .np-count-pill,
        .np-child-filter-option:has(.np-filter-checkbox:checked) .np-count-pill {
            background: #ffffff;
            color: #be123c;
        }

        .np-child-filter-option.is-selected .np-selected-mark,
        .np-child-filter-option:has(.np-filter-checkbox:checked) .np-selected-mark,
        .np-category-group.is-parent-selected .np-parent-filter-option .np-selected-mark,
        .np-category-group:has(> .np-category-summary .np-filter-checkbox:checked) .np-parent-filter-option .np-selected-mark {
            background: #e91d33;
            color: #ffffff;
        }

        .np-filter-empty-state {
            display: none;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            padding: 16px;
            text-align: center;
        }

        .np-filter-mobile-open {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .np-mobile-filter-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 22px;
            border-radius: 999px;
            background: #e91d33;
            color: #fff;
            font-size: 12px;
            font-weight: 900;
            padding: 0 7px;
        }

        .np-filter-drawer {
            display: flex;
            flex-direction: column;
            width: min(92vw, 430px);
            max-width: 100%;
            height: 100%;
            overflow: hidden;
            background: #fff;
            box-shadow: -24px 0 60px rgba(15, 23, 42, .28);
            padding: 18px;
        }

        .np-filter-close {
            position: absolute;
            right: 16px;
            top: 14px;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            background: #fff;
            color: #111827;
            font-size: 22px;
            font-weight: 800;
            line-height: 1;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .08);
        }

        @media (min-width: 1024px) {
            .np-product-layout.has-filters {
                grid-template-columns: 300px minmax(0, 1fr);
                align-items: start;
            }

            .np-filter-shell {
                display: flex;
                flex-direction: column;
                overflow: hidden;
                padding: 18px;
                position: sticky;
                top: 8rem;
            }
        }

        @media (min-width: 1280px) {
            .np-product-layout.has-filters {
                grid-template-columns: 320px minmax(0, 1fr);
            }
        }

        @media (max-width: 1023px) {
            .np-product-filter-form {
                gap: 14px;
            }

            .np-filter-scroll {
                padding-right: 2px;
            }

            .np-child-list {
                margin-left: 30px;
            }

        }
    </style>
@endonce

<form method="GET" action="{{ route('products.index') }}" class="np-product-filter-form" aria-label="Filter products by category" data-np-category-filter-form>
    @if(filled($query))
        <input type="hidden" name="q" value="{{ $query }}">
    @endif

    @if(filled($tag))
        <input type="hidden" name="tag" value="{{ $tag }}">
    @endif

    <div class="np-category-filter-card">
        <div class="np-filter-top">
            <div class="np-filter-searchbox">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="10" cy="10" r="7"></circle>
                    <path d="M21 21l-6-6"></path>
                </svg>
                <input
                    id="{{ $idPrefix }}-category-search"
                    class="np-filter-category-search"
                    type="search"
                    placeholder="Search categories"
                    autocomplete="off"
                    data-np-category-search
                >
            </div>

            <div class="np-filter-heading-row">
                <p class="np-filter-section-label">Shop by category</p>
                @if($selectedCount > 0)
                    <span class="np-filter-active-chip" data-np-selected-count>{{ $selectedCount }} active</span>
                @else
                    <span class="np-filter-active-chip" data-np-selected-count style="display:none"></span>
                @endif
            </div>
        </div>

        @if($filters !== [])
            <div class="np-filter-scroll">
                <div class="np-category-list" data-np-category-list>
                    @foreach($filters as $parent)
                        @php
                            $parentSelected = in_array((int) $parent['id'], $selectedCategoryIds, true);
                            $parentOpen = $parentSelected
                                || (bool) ($parent['has_selected_child'] ?? false)
                                || $loop->first;
                            $parentFieldId = $idPrefix.'-category-'.$parent['id'];
                            $children = $parent['children'] ?? [];
                            $searchText = strtolower(trim((string) ($parent['label'] ?? '')));
                        @endphp

                        <details
                            @class([
                                'np-category-group',
                                'is-selected' => $parentSelected || (bool) ($parent['has_selected_child'] ?? false),
                                'is-parent-selected' => $parentSelected,
                            ])
                            data-np-category-group
                            data-filter-text="{{ $searchText }}"
                            @if($parentOpen) open @endif
                        >
                            <summary class="np-category-summary">
                                <label for="{{ $parentFieldId }}" class="np-parent-filter-option" onclick="event.stopPropagation()">
                                    <input
                                        id="{{ $parentFieldId }}"
                                        type="checkbox"
                                        name="categories[]"
                                        value="{{ $parent['id'] }}"
                                        @checked($parentSelected)
                                        class="np-filter-checkbox"
                                        data-np-category-checkbox
                                    >
                                    <span class="np-category-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $iconForCategory((string) ($parent['label'] ?? '')) !!}</svg>
                                    </span>
                                    <span class="np-parent-title">{{ $parent['label'] }}</span>
                                    <span class="np-selected-mark" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                    </span>
                                </label>
                                <span class="np-parent-meta" aria-hidden="true">
                                    <span class="np-count-pill">{{ $parent['count'] }}</span>
                                    @if($children !== [])
                                        <span class="np-chevron">⌄</span>
                                    @endif
                                </span>
                            </summary>

                            @if($children !== [])
                                <div class="np-child-list">
                                    @foreach($children as $child)
                                        @php
                                            $fieldId = $idPrefix.'-category-'.$child['id'];
                                            $childSelected = in_array((int) $child['id'], $selectedCategoryIds, true);
                                            $childSearchText = strtolower(trim((string) ($child['label'] ?? '')));
                                        @endphp
                                        <label
                                            for="{{ $fieldId }}"
                                            @class(['np-child-filter-option', 'is-selected' => $childSelected])
                                            data-np-child-option
                                            data-filter-text="{{ $childSearchText }}"
                                        >
                                            <input
                                                id="{{ $fieldId }}"
                                                type="checkbox"
                                                name="categories[]"
                                                value="{{ $child['id'] }}"
                                                @checked($childSelected)
                                                class="np-filter-checkbox"
                                                data-np-category-checkbox
                                            >
                                            <span class="np-child-name">{{ $child['label'] }}</span>
                                            <span class="np-parent-meta" aria-hidden="true">
                                                <span class="np-count-pill">{{ $child['count'] }}</span>
                                                <span class="np-selected-mark">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                                </span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </details>
                    @endforeach
                </div>
                <div class="np-filter-empty-state" data-np-filter-empty>No matching categories found.</div>
            </div>
        @endif
    </div>

</form>

@once
    <script>
        document.addEventListener('input', function (event) {
            const search = event.target.closest('[data-np-category-search]');

            if (! search) {
                return;
            }

            const form = search.closest('[data-np-category-filter-form]');
            const query = search.value.trim().toLowerCase();
            const groups = Array.from(form.querySelectorAll('[data-np-category-group]'));
            let visibleGroups = 0;

            groups.forEach(function (group) {
                const groupText = (group.dataset.filterText || '').toLowerCase();
                const children = Array.from(group.querySelectorAll('[data-np-child-option]'));
                const groupMatches = query === '' || groupText.includes(query);
                let visibleChildren = 0;

                children.forEach(function (child) {
                    const childText = (child.dataset.filterText || '').toLowerCase();
                    const childMatches = query === '' || childText.includes(query) || groupMatches;
                    child.hidden = ! childMatches;

                    if (childMatches) {
                        visibleChildren += 1;
                    }
                });

                const shouldShow = groupMatches || visibleChildren > 0;
                group.hidden = ! shouldShow;

                if (query !== '' && shouldShow) {
                    group.open = true;
                }

                if (shouldShow) {
                    visibleGroups += 1;
                }
            });

            const empty = form.querySelector('[data-np-filter-empty]');
            if (empty) {
                empty.style.display = visibleGroups === 0 ? 'block' : 'none';
            }
        });

        document.addEventListener('change', function (event) {
            const checkbox = event.target.closest('[data-np-category-checkbox]');

            if (! checkbox) {
                return;
            }

            const form = checkbox.closest('[data-np-category-filter-form]');

            if (checkbox.checked) {
                form.querySelectorAll('[data-np-category-checkbox]').forEach(function (otherCheckbox) {
                    if (otherCheckbox !== checkbox) {
                        otherCheckbox.checked = false;
                    }
                });
            }

            const groups = Array.from(form.querySelectorAll('[data-np-category-group]'));

            groups.forEach(function (group) {
                const parentChecked = !! group.querySelector(':scope > .np-category-summary [data-np-category-checkbox]:checked');
                const childChecked = !! group.querySelector('.np-child-list [data-np-category-checkbox]:checked');

                group.classList.toggle('is-parent-selected', parentChecked);
                group.classList.toggle('is-selected', parentChecked || childChecked);
            });

            const count = form.querySelectorAll('[data-np-category-checkbox]:checked').length;
            const counter = form.querySelector('[data-np-selected-count]');

            if (counter) {
                counter.textContent = count > 0 ? count + ' active' : '';
                counter.style.display = count > 0 ? 'inline-flex' : 'none';
            }

            if (form.dataset.npAutoSubmitting === '1') {
                return;
            }

            form.dataset.npAutoSubmitting = '1';

            window.setTimeout(function () {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                    return;
                }

                form.submit();
            }, 80);
        });
    </script>
@endonce
