@props([
    'name' => 'q',
    'value' => '',
    'label' => 'Search',
    'placeholder' => 'Search...',
    'maxlength' => null,
])
{{-- Shared search field. Existing GET name/value are preserved. --}}
<div class="np-admin-filter-search">
    <svg class="np-admin-filter-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
        <circle cx="10.8" cy="10.8" r="6.5" />
        <path d="m16 16 4.4 4.4" />
    </svg>
    <input class="admin-input np-admin-filter-search__input" type="search"
           name="{{ $name }}" value="{{ $value }}" placeholder="{{ $placeholder }}"
           aria-label="{{ $label }}"
           @if($maxlength !== null) maxlength="{{ $maxlength }}" @endif>
</div>
