@props([
    'label',
    'name',
    'type' => 'text',
    'placeholder' => '',
    'value' => null,
    'full' => false,
    'options' => [],
    'textarea' => false,
    'required' => false,
    'hint' => null,
])

<div class="{{ $full ? 'sm:col-span-2' : '' }}">
    <label for="{{ $name }}" class="text-sm font-black text-brand-ink">{{ $label }} @if($required)<span class="nps-required" aria-hidden="true">*</span>@else<span class="nps-optional">(optional)</span>@endif</label>
    @if ($textarea)
        <textarea id="{{ $name }}" name="{{ $name }}" rows="4" placeholder="{{ $placeholder }}" class="mt-2 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800 outline-none transition focus:border-brand-blue focus:bg-white">{{ old($name, $value) }}</textarea>
    @elseif ($options)
        <select id="{{ $name }}" name="{{ $name }}" class="mt-2 h-12 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 text-sm font-bold text-slate-800 outline-none transition focus:border-brand-blue focus:bg-white">
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" placeholder="{{ $placeholder }}" class="mt-2 h-12 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 text-sm font-semibold text-slate-800 outline-none transition focus:border-brand-blue focus:bg-white">
    @endif

    @if ($hint)
        <p class="nps-help">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="nps-error" role="alert">{{ $message }}</p>
    @enderror
</div>
