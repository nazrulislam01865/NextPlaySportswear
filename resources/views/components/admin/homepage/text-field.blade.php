@props(['name', 'label', 'value' => null, 'oldName' => null, 'textarea' => false, 'help' => null, 'required' => false])
@php($resolvedOld = $oldName ?: str_replace(['][', '[', ']'], ['.', '.', ''], $name))
<label class="np-home-admin__field block">
    <span class="np-home-admin__label">{{ $label }} @if($required)<span aria-hidden="true">*</span>@endif</span>
    @if($textarea)
        <textarea name="{{ $name }}" rows="4" @if($required) required @endif class="np-home-admin__input">{{ old($resolvedOld, $value) }}</textarea>
    @else
        <input type="text" name="{{ $name }}" value="{{ old($resolvedOld, $value) }}" @if($required) required @endif class="np-home-admin__input">
    @endif
    @if($help)<span class="np-home-admin__help">{{ $help }}</span>@endif
    @error($resolvedOld)<span class="np-home-admin__error">{{ $message }}</span>@enderror
</label>
