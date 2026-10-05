@props([
    'settings',
    'settingKey',
    'label',
    'inputPrefix' => 'settings',
    'filePrefix' => 'icon_files',
    'clearPrefix' => 'clear_icons',
])

@php
    $path = trim((string) ($settings[$settingKey] ?? ''));
    $preview = $path !== '' ? \App\Support\PublicMedia::url($path) : null;
    $inputId = 'product-detail-icon-'.str_replace('_', '-', $settingKey);
@endphp

<div class="np-pdui-icon-field" x-data="{ fileName: '' }">
    <div class="np-pdui-icon-preview" aria-hidden="true">
        @if($preview)
            <img src="{{ $preview }}" alt="" class="np-pdui-icon-preview__image">
        @else
            <span>Icon</span>
        @endif
    </div>

    <div class="np-pdui-icon-content">
        <strong class="np-pdui-icon-label">{{ $label }}</strong>
        <input type="hidden" name="{{ $inputPrefix }}[{{ $settingKey }}]" value="{{ $path }}">
        <input
            id="{{ $inputId }}"
            class="sr-only"
            type="file"
            name="{{ $filePrefix }}[{{ $settingKey }}]"
            accept=".svg,.png,.jpg,.jpeg,.webp,.avif,image/svg+xml,image/png,image/jpeg,image/webp,image/avif"
            x-on:change="fileName = $event.target.files?.[0]?.name || ''"
        >
        <div class="np-pdui-icon-actions">
            <label for="{{ $inputId }}" class="btn btn-white np-pdui-upload-button">Upload icon</label>
            <span class="np-pdui-file-name" x-text="fileName || '{{ $path !== '' ? 'Current icon active' : 'No file selected' }}'"></span>
        </div>
        <small class="np-pdui-icon-help">SVG, PNG, JPG, WebP or AVIF · max 2 MB</small>

        @if($path !== '')
            <label class="np-pdui-remove-icon">
                <input type="hidden" name="{{ $clearPrefix }}[{{ $settingKey }}]" value="0">
                <input type="checkbox" name="{{ $clearPrefix }}[{{ $settingKey }}]" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red">
                <span>Use default icon instead</span>
            </label>
        @endif
    </div>
</div>
