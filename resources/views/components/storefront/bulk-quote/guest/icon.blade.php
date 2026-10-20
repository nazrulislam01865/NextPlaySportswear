@props(['name' => 'file', 'size' => 22])
<svg {{ $attributes->merge(['width' => $size, 'height' => $size, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.9', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
    @switch($name)
        @case('check')<path d="m5 12 4.5 4.5L19 7" />@break
        @case('file')<path d="M5 2.5h9l5 5V21H5z" /><path d="M14 2.5V8h5M8 12h8M8 16h8" />@break
        @case('mail')<rect x="2.5" y="5" width="19" height="14" rx="2" /><path d="m3 7 9 7 9-7" />@break
        @case('clock')<circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />@break
        @case('copy')<rect x="8" y="8" width="12" height="13" rx="2" /><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h3" />@break
        @case('search')<circle cx="10.7" cy="10.7" r="7.2" /><path d="m16 16 5 5" />@break
        @case('eye')<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" />@break
        @case('download')<path d="M12 3v12m-4-4 4 4 4-4M4 17v4h16v-4" />@break
        @case('upload')<path d="M12 16V3m-5 5 5-5 5 5M4 15v6h16v-6" />@break
        @case('calendar')<rect x="3" y="5" width="18" height="16" rx="2" /><path d="M7 3v4M17 3v4M3 10h18" />@break
        @case('message')<path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5H5l1.8-4.2A8.5 8.5 0 1 1 21 11.5Z" />@break
        @case('headphones')<path d="M3 13v-2a9 9 0 0 1 18 0v2M3 13h4v7H5a2 2 0 0 1-2-2v-5Zm18 0h-4v7h2a2 2 0 0 0 2-2v-5Z" />@break
        @case('truck')<path d="M2 6h13v11H2zM15 10h4l3 4v3h-7" /><circle cx="6" cy="18" r="2" /><circle cx="18" cy="18" r="2" />@break
        @case('calculator')<rect x="5" y="2" width="14" height="20" rx="2" /><path d="M8 6h8v4H8zM8 14h1m3 0h1m3 0h1M8 18h1m3 0h1m3 0h1" />@break
        @case('cart')<path d="M2 3h3l2.2 13h12L22 7H6" /><circle cx="9" cy="20" r="1" /><circle cx="18" cy="20" r="1" />@break
        @case('arrow-right')<path d="M4 12h16m-6-6 6 6-6 6" />@break
        @case('arrow-left')<path d="M20 12H4m6-6-6 6 6 6" />@break
        @case('bell')<path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4" />@break
        @case('edit')<path d="m4 17 13-13 3 3L7 20H4zM15 6l3 3" />@break
        @case('shield')<path d="m12 2 8 4v6c0 5-3.3 8-8 10-4.7-2-8-5-8-10V6z" /><path d="m8 12 3 3 5-6" />@break
    @endswitch
</svg>
