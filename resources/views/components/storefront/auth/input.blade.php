@props([
    'name',
    'label',
    'type' => 'text',
    'placeholder' => '',
    'value' => null,
    'autocomplete' => null,
    'required' => false,
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
        value="{{ $type === 'password' ? '' : old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($required) required @endif
        {{ $attributes->merge([
            'class' => 'h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-brand-blue focus:ring-4 focus:ring-brand-blue/10',
        ]) }}
    >

    @error($name)
        <p class="mt-2 text-sm font-bold text-brand-red">{{ $message }}</p>
    @enderror
</div>
