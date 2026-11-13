@props([
    'id',
    'name',
    'label' => 'Choose an option',
    'searchPlaceholder' => 'Search options...',
    'searchable' => false,
])

{{-- Progressive enhancement: the original select remains the submitted field and
     works normally if JavaScript is unavailable. Use data-depth on option tags
     for hierarchical choices (for example, product categories). --}}
<div class="np-admin-select" data-np-admin-select
     data-np-select-label="{{ $label }}"
     data-np-select-search-placeholder="{{ $searchPlaceholder }}"
     data-np-select-searchable="{{ $searchable ? 'true' : 'false' }}">
    <select id="{{ $id }}" name="{{ $name }}"
            {{ $attributes->merge(['aria-label' => $label, 'class' => 'admin-input product-control product-select-control np-admin-select-native']) }}>
        {{ $slot }}
    </select>
</div>

@once
    @push('admin-page-styles')
        <link rel="stylesheet" href="{{ asset('css/nextplay-admin-searchable-select.css') }}?v=20261113b">
    @endpush
    @push('admin-page-scripts')
        <script defer src="{{ asset('js/nextplay-admin-searchable-select.js') }}?v=20261113b"></script>
    @endpush
@endonce
