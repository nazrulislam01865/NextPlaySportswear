@props(['title' => 'Item', 'subtitle' => null])
<div {{ $attributes->class('np-home-admin__item-card rounded-2xl p-4') }}>
    <div class="mb-4 flex items-start justify-between gap-3">
        <div><h3 class="font-bold">{{ $title }}</h3>@if($subtitle)<p class="np-home-admin__muted text-xs">{{ $subtitle }}</p>@endif</div>
        {{ $actions ?? '' }}
    </div>
    {{ $slot }}
</div>
