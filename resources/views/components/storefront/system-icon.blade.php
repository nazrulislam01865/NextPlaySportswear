@props([
    'type',
])

<span class="np-system-card__icon" aria-hidden="true">
    @switch($type)
        @case('mail-check')
            <svg viewBox="0 0 96 82" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="8" y="9" width="67" height="50" rx="6" stroke="currentColor" stroke-width="4"/>
                <path d="M13 15L41.5 38L70 15" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="72" cy="58" r="16" fill="white" stroke="currentColor" stroke-width="4"/>
                <path d="M65.5 58.2L70.1 62.7L79.1 53.7" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            @break

        @case('lock')
            <svg viewBox="0 0 86 96" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M24 40V28C24 17.5 32.5 9 43 9C53.5 9 62 17.5 62 28V40" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
                <rect x="14" y="39" width="58" height="48" rx="8" stroke="currentColor" stroke-width="5"/>
                <circle cx="43" cy="60" r="5.5" fill="currentColor"/>
                <path d="M43 65.5V73" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
            </svg>
            @break
    @endswitch
</span>
