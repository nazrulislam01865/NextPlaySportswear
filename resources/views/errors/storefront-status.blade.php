<x-layouts.storefront :seo="[
    'title' => $code.' | '.config('storefront.name', 'NextPlay Sportswear'),
    'description' => $messageText,
    'robots' => 'noindex, nofollow',
]">
    <x-storefront.system-card
        :code="$code"
        :title="$title"
        :icon="$icon ?? null"
        title-id="storefront-error-title"
    >
        <x-slot:message>
            {{ $messageText }}
        </x-slot:message>

        @if (($primaryAction ?? null) === 'retry')
            <button type="button" class="np-system-card__primary" onclick="window.location.reload()">
                {{ $primaryLabel }}
            </button>
        @else
            <a href="{{ $primaryUrl }}" class="np-system-card__primary">
                {{ $primaryLabel }}
            </a>
        @endif

        @if (filled($secondaryLabel ?? null))
            <x-slot:footer>
                @if (($secondaryAction ?? null) === 'logout')
                    @if (auth('web')->check())
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="np-system-card__secondary-link np-system-card__secondary-button">
                                {{ $secondaryLabel }}
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="np-system-card__secondary-link">
                            {{ $secondaryLabel }}
                        </a>
                    @endif
                @else
                    <a href="{{ $secondaryUrl }}" class="np-system-card__secondary-link">
                        {{ $secondaryLabel }}
                    </a>
                @endif
            </x-slot:footer>
        @endif
    </x-storefront.system-card>
</x-layouts.storefront>
