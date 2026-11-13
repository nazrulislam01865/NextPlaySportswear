@props(['name' => 'circle', 'size' => 16])
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes }}>
@switch($name)
@case('grid')<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>@break
@case('cart')<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>@break
@case('file')<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h6"/>@break
@case('palette')<circle cx="13.5" cy="6.5" r=".6"/><circle cx="17.5" cy="10.5" r=".6"/><circle cx="8.5" cy="7.5" r=".6"/><path d="M12 22a10 10 0 1 1 10-10 3 3 0 0 1-3 3h-2a2 2 0 0 0-2 2c0 2.2-1 5-3 5Z"/>@break
@case('truck')<path d="M10 17h4V5H2v12h3m9-8h5l3 4v4h-3M14 9v8"/><circle cx="7.5" cy="17" r="2"/><circle cx="17.5" cy="17" r="2"/>@break
@case('return')<path d="M9 14 4 9l5-5M4 9h10a6 6 0 0 1 0 12h-2"/>@break
@case('package')<path d="m12 2 9 5-9 5-9-5 9-5Zm-9 5v10l9 5 9-5V7M12 12v10"/>@break
@case('tag')<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8Z"/><circle cx="7" cy="7" r="1"/>@break
@case('users')<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M16 4a4 4 0 0 1 0 8M22 21v-2a4 4 0 0 0-3-3.87"/><circle cx="9" cy="7" r="4"/>@break
@case('chart')<path d="M3 3v18h18M7 16l4-5 4 2 5-7"/>@break
@case('settings')<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.07.07-2.1 2.1-.07-.07a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.05 1.56V21h-3v-.8a1.7 1.7 0 0 0-1.05-1.56 1.7 1.7 0 0 0-1.88.34l-.07.07-2.1-2.1.07-.07A1.7 1.7 0 0 0 7 15a1.7 1.7 0 0 0-1.56-1.05H4v-3h1.44A1.7 1.7 0 0 0 7 9.9a1.7 1.7 0 0 0-.34-1.88l-.07-.07 2.1-2.1.07.07a1.7 1.7 0 0 0 1.88.34A1.7 1.7 0 0 0 11.7 4.7V4h3v.7a1.7 1.7 0 0 0 1.05 1.56 1.7 1.7 0 0 0 1.88-.34l.07-.07 2.1 2.1-.07.07a1.7 1.7 0 0 0-.34 1.88 1.7 1.7 0 0 0 1.56 1.05H22v3h-1.05A1.7 1.7 0 0 0 19.4 15Z"/>@break
@case('search')<circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/>@break
@case('bell')<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>@break
@case('calendar')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18"/>@break
@case('download')<path d="M12 3v12m-4-4 4 4 4-4M4 17v3h16v-3"/>@break
@case('plus')<path d="M12 5v14M5 12h14"/>@break
@case('arrow')<path d="M5 12h14m-5-5 5 5-5 5"/>@break
@case('dollar')<path d="M12 2v20m5-16H9a4 4 0 0 0 0 8h6a4 4 0 0 1 0 8H6"/>@break
@case('check')<path d="m5 12 4 4L19 6"/>@break
@case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
@case('help')<circle cx="12" cy="12" r="10"/><path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 2-2.5 2.4-2.5 4.2M12 17h.01"/>@break
@default<circle cx="12" cy="12" r="9"/>
@endswitch
</svg>
