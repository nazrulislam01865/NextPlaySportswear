@props([
    'title',
    'code' => null,
    'icon' => null,
    'titleId' => 'np-system-card-title',
])

<section class="np-customer-login np-system-screen" aria-labelledby="{{ $titleId }}">
    <div class="np-customer-login__card np-system-card{{ filled($code) ? ' np-system-card--has-code' : '' }}{{ filled($icon) ? ' np-system-card--has-icon' : '' }}">
        @if (filled($code))
            <div class="np-system-card__code" aria-hidden="true">{{ $code }}</div>
        @endif

        <h1 id="{{ $titleId }}" class="np-customer-login__title np-system-card__title">
            {{ $title }}
        </h1>

        @if (filled($icon))
            <x-storefront.system-icon :type="$icon" />
        @endif

        @isset($message)
            <div class="np-system-card__message">
                {{ $message }}
            </div>
        @endisset

        <div class="np-system-card__body">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="np-system-card__footer">
                {{ $footer }}
            </div>
        @endisset
    </div>
</section>
