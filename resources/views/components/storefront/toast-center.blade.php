@props([
    'initial' => session('toast'),
])

<div
    class="np-toast-region"
    x-data="storefrontToastCenter(@js($initial))"
    x-init="init()"
    @storefront-toast.window="show($event.detail)"
    aria-live="polite"
    aria-atomic="false"
>
    <template x-for="toast in toasts" :key="toast.id">
        <article
            class="np-toast"
            :class="`np-toast--${toast.type}`"
            :style="{ '--np-toast-duration': `${toast.duration}ms` }"
            role="status"
        >
            <div class="np-toast__icon" aria-hidden="true">
                <svg x-show="toast.type === 'success'" viewBox="0 0 24 24" fill="none">
                    <path d="m5 12.5 4.2 4.2L19.5 6.5" stroke="currentColor" stroke-width="2.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <svg x-show="toast.type === 'info'" viewBox="0 0 24 24" fill="none">
                    <path d="M12 10.5v6M12 7.25h.01" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
                <svg x-show="toast.type === 'warning'" viewBox="0 0 24 24" fill="none">
                    <path d="M12 8.5v5M12 17h.01" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
                <svg x-show="toast.type === 'error'" viewBox="0 0 24 24" fill="none">
                    <path d="m8 8 8 8m0-8-8 8" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </div>

            <p class="np-toast__copy">
                <strong x-text="`${toast.title}:`"></strong>
                <span x-text="toast.message"></span>
            </p>

            <button
                type="button"
                class="np-toast__close"
                @click="dismiss(toast.id)"
                aria-label="Dismiss notification"
            >
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m7 7 10 10m0-10L7 17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>

            <span class="np-toast__progress" aria-hidden="true"></span>
        </article>
    </template>
</div>
