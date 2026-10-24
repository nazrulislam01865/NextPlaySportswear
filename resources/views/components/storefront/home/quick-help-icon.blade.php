@props(['name' => 'ruler'])

<svg class="np-home-quick-help-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('artwork')
            <path d="M13 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h9" />
            <path d="M13 3v5h5M8 12h6M8 16h4" />
            <path d="m15 19 5.2-5.2a1.4 1.4 0 0 0-2-2L13 17v3h3z" />
            @break
        @case('package')
            <path d="m3 7 9-4 9 4v10l-9 4-9-4z" />
            <path d="m3 7 9 5 9-5M12 12v9M7.5 5 16.5 10" />
            @break
        @case('chat')
            <path d="M20.5 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-3.6-.8L3 21l1.8-5.4A8.5 8.5 0 1 1 20.5 11.5Z" />
            <circle cx="8.5" cy="11.5" r=".7" fill="currentColor" stroke="none" />
            <circle cx="12" cy="11.5" r=".7" fill="currentColor" stroke="none" />
            <circle cx="15.5" cy="11.5" r=".7" fill="currentColor" stroke="none" />
            @break
        @default
            <path d="m3.6 15.2 11.6-11.6a2 2 0 0 1 2.8 0l2.4 2.4a2 2 0 0 1 0 2.8L8.8 20.4a2 2 0 0 1-2.8 0L3.6 18a2 2 0 0 1 0-2.8Z" />
            <path d="m11 8 2 2M8 11l2 2m4-8 2 2M5 14l2 2" />
    @endswitch
</svg>
