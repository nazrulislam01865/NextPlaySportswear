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
            <span class="text-brand-red">*</span>
        @endif
    </label>

    <input
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
        <p class="mt-2 text-xs font-semibold leading-5 text-slate-500">{{ $help }}</p>
    @endif

    @error($name)
        <p class="mt-2 text-sm font-bold text-brand-red">{{ $message }}</p>
    @enderror
</div>
