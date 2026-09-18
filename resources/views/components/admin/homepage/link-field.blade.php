@props(['name', 'label', 'value' => null, 'oldName' => null, 'help' => null])
@php($resolvedOld = $oldName ?: str_replace(['][', '[', ']'], ['.', '.', ''], $name))
<label class="np-home-admin__field block">
    <span class="np-home-admin__label">{{ $label }}</span>
    <input type="text" name="{{ $name }}" value="{{ old($resolvedOld, $value) }}" class="np-home-admin__input" placeholder="/products or https://...">
    @if($help)<span class="np-home-admin__help">{{ $help }}</span>@endif
    @error($resolvedOld)<span class="np-home-admin__error">{{ $message }}</span>@enderror
</label>
