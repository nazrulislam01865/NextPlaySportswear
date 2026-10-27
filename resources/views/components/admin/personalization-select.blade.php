@props([
    'name',
    'id',
    'options' => [],
    'selected' => '',
    'placeholder' => 'Select an option',
    'searchable' => true,
    'clearable' => true,
    'searchPlaceholder' => 'Search options...',
])
@php
    $selectOptions = array_values($options);
    $selectedValue = (string) $selected;
@endphp
<div class="pt-select" x-data="personalizationSelect(@js($selectOptions), @js($selectedValue))" @click.outside="close()" @keydown.escape="close()" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="open ? chooseVisible() : toggle()">
    <input type="hidden" id="{{ $id }}-value" name="{{ $name }}" :value="value">
    <div class="pt-select-control">
        <button type="button" id="{{ $id }}" class="pt-input pt-select-trigger" :class="{'is-open': open, 'is-placeholder': !value, 'has-value': value !== ''}" :aria-expanded="open.toString()" aria-haspopup="listbox" aria-controls="{{ $id }}-list" @click="toggle()">
            <span class="pt-select-caption" x-text="label || @js($placeholder)" :title="label"></span>
            <svg x-show="!value" x-cloak viewBox="0 0 20 20" width="16" height="16" fill="none" aria-hidden="true"><path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        @if($clearable)
            <button type="button" class="pt-select-clear" x-show="value !== ''" x-cloak @click.stop="choose('')" aria-label="Clear selection" title="Clear selection">×</button>
        @endif
    </div>
    <div class="pt-select-panel" x-show="open" x-cloak>
        @if($searchable)
            <label class="pt-select-search"><svg viewBox="0 0 20 20" width="16" height="16" fill="none" aria-hidden="true"><circle cx="8.5" cy="8.5" r="5.5" stroke="currentColor" stroke-width="1.6"/><path d="m13 13 4.3 4.3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg><input type="search" x-ref="searchInput" x-model="search" @input="activeIndex = 0" placeholder="{{ $searchPlaceholder }}" aria-label="Search options" autocomplete="off" /></label>
        @endif
        <div class="pt-select-options" id="{{ $id }}-list" role="listbox" aria-label="{{ $placeholder }}">
            <template x-for="option in visible()" :key="option.value">
                <button type="button" class="pt-select-option" :class="{'is-selected': value === option.value, 'is-highlighted': open && visible()[activeIndex]?.value === option.value}" :aria-selected="(value === option.value).toString()" role="option" @click="choose(option.value)">
                    <span x-text="option.label"></span><span class="pt-select-tick" x-show="value === option.value" aria-hidden="true">✓</span>
                </button>
            </template>
            <p class="pt-select-empty" x-show="visible().length === 0">No matching options</p>
        </div>
        <div class="pt-select-result" x-show="filteredCount() > 60" x-text="'Showing first 60 matches · Type to narrow results'"></div>
    </div>
</div>
