@props([
    'name',
    'label',
    'placeholder' => '',
    'autocomplete' => 'new-password',
    'required' => false,
    'autofocus' => false,
])

<div x-data="{ visible: false }">
    <label for="{{ $name }}" class="mb-2 block text-sm font-black text-slate-800">
        {{ $label }}
        @if ($required)
            <span class="text-brand-red">*</span>
        @endif
    </label>

    <div class="relative">
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="password"
            :type="visible ? 'text' : 'password'"
            value=""
            placeholder="{{ $placeholder }}"
            autocomplete="{{ $autocomplete }}"
            @if ($required) required @endif
            @if ($autofocus) autofocus @endif
            {{ $attributes->merge([
                'class' => 'h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 pr-12 text-sm font-semibold text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-brand-blue focus:ring-4 focus:ring-brand-blue/10',
            ]) }}
        >

        <button
            type="button"
            class="absolute inset-y-0 right-0 grid w-12 place-items-center text-slate-500 transition hover:text-brand-blue focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-blue/30"
            @click="visible = ! visible"
            aria-label="Show password"
            :aria-label="visible ? 'Hide password' : 'Show password'"
            aria-pressed="false"
            :aria-pressed="visible.toString()"
            aria-controls="{{ $name }}"
        >
            <svg
                x-show="! visible"
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="M2.06 12.35a1 1 0 0 1 0-.7C3.72 7.73 7.16 5 12 5s8.28 2.73 9.94 6.65a1 1 0 0 1 0 .7C20.28 16.27 16.84 19 12 19s-8.28-2.73-9.94-6.65Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>

            <svg
                x-cloak
                x-show="visible"
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c4.84 0 8.28 2.73 9.94 6.65a1 1 0 0 1 0 .7 11.05 11.05 0 0 1-1.43 2.49"></path>
                <path d="M6.61 6.61A11.03 11.03 0 0 0 2.06 11.65a1 1 0 0 0 0 .7C3.72 16.27 7.16 19 12 19a10.8 10.8 0 0 0 5.39-1.39"></path>
                <path d="m3 3 18 18"></path>
            </svg>
        </button>
    </div>

    @error($name)
        <p class="mt-2 text-sm font-bold text-brand-red">{{ $message }}</p>
    @enderror
</div>
