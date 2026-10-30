@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'placeholder' => '',
    'autocomplete' => null,
    'required' => false,
    'help' => null,
])

<div>
    <label for="{{ $name }}" class="mb-2 block text-sm font-black text-slate-800">
        {{ $label }}
        @if ($required)
            <span class="nps-required" aria-hidden="true">*</span>
        @else
            <span class="nps-optional">(optional)</span>
        @endif
    </label>

    <input
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-server-error" @enderror
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($required) required @endif
        class="h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-brand-blue focus:ring-4 focus:ring-brand-blue/10"
    >

    @if ($help)
        <p class="nps-help">{{ $help }}</p>
    @endif

    @error($name)
        <p id="{{ $name }}-server-error" class="nps-error" role="alert">{{ $message }}</p>
    @enderror
</div>
