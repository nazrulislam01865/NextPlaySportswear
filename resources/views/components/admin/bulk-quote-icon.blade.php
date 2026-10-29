@props(['name' => 'file'])
<svg {{ $attributes->class(['np-bq-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('home')<path d="M3 10 12 3l9 7v10H3V10Z"/><path d="M9 20v-7h6v7"/>@break
        @case('bag')<rect x="4" y="7" width="16" height="14" rx="2"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>@break
        @case('star')<path d="m12 2 3.1 6.4 7.1 1-5.1 5 .9 7-6-3.3-6 3.3.9-7-5.1-5 7.1-1Z"/>@break
        @case('tag')<path d="M3 12V4h8l10 10-8 8L3 12Z"/><circle cx="8" cy="8" r="1"/>@break
        @case('ticket')<path d="M3 7a3 3 0 0 0 0 6v4h18v-4a3 3 0 0 1 0-6V4H3v3Z"/><path d="m10 15 4-6m-4 0h.01M14 15h.01"/>@break
        @case('store')<path d="M4 10v11h16V10M3 4h18l1 6c0 4-5 5-7 2-2 3-5 3-7 0-2 3-7 2-7-2l2-6Z"/><path d="M9 21v-7h6v7"/>@break
        @case('cube')<path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="M3 7v10l9 5 9-5V7M12 12v10"/>@break
        @case('user')<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2H4Z"/>@break
        @case('truck')<path d="M2 6h12v11H2zM14 10h4l4 4v3h-8z"/><circle cx="6" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>@break
        @case('settings')<path d="M10 2h4l.7 2.3 2.2 1.2 2.3-.7 2 3.4-1.6 1.8v2.4l1.6 1.8-2 3.4-2.3-.7-2.2 1.2L14 21h-4l-.7-2.3-2.2-1.2-2.3.7-2-3.4 1.6-1.8v-2.4L2.8 8.8l2-3.4 2.3.7 2.2-1.2L10 2Z"/><circle cx="12" cy="11.6" r="3"/>@break
        @case('paperclip')<path d="m20 11-8.5 8.5a5 5 0 0 1-7-7L14 3a3.5 3.5 0 0 1 5 5l-9 9a2 2 0 0 1-3-3l8-8"/>@break
        @case('file')<path d="M6 2h8l5 5v15H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z"/><path d="M14 2v6h5M8 13h8M8 17h8"/>@break
        @case('refresh')<path d="M20 7v5h-5M4 17v-5h5"/><path d="M5 9a8 8 0 0 1 14-2l1 5M4 12l1 5a8 8 0 0 0 14-2"/>@break
        @case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
        @case('eye')<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>@break
        @case('download')<path d="M12 3v12m-5-5 5 5 5-5M4 17v4h16v-4"/>@break
        @case('chevron')<path d="m6 9 6 6 6-6"/>@break
        @case('arrow')<path d="m15 18-6-6 6-6"/>@break
        @default <path d="M4 3h16v18H4z"/>
    @endswitch
</svg>
