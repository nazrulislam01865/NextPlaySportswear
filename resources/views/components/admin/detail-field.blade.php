@props(['label', 'wide' => false])
<div {{ $attributes->class(['admin-detail-field', 'admin-detail-field--wide' => $wide]) }}>
    <dt class="admin-detail-field__label">{{ $label }}</dt>
    <dd class="admin-detail-field__value">{{ $slot }}</dd>
</div>
