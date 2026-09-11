@props([
    'label' => 'Filters',
])

<form
    method="GET"
    aria-label="{{ $label }}"
    {{ $attributes->class(['admin-filter-bar mb-6 rounded-3xl border border-slate-200 bg-white p-4 shadow-card']) }}
>
    <div class="admin-filter-bar__scroll">
        <div class="admin-filter-bar__row">
            {{ $slot }}
        </div>
    </div>
</form>
