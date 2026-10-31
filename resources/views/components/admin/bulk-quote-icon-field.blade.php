@props(['iconKey', 'label', 'url' => null, 'canManage' => false])

@php
    $fallbackIcon = match ($iconKey) {
        'product' => 'box',
        'contact' => 'user',
        'delivery' => 'truck',
        'attachments' => 'attachment',
        'help' => 'message-circle',
        default => 'file-text',
    };
    $inputId = 'bulk-quote-icon-'.$iconKey;
    $hasCustom = filled($url);
@endphp

<article class="np-pdui-control-card np-bqic-card"
    x-data="{
        fileName: '', previewUrl: null, removeSelected: false,
        chooseFile(event) {
            if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
            const file = event.target.files?.[0];
            this.fileName = file?.name || '';
            this.previewUrl = file ? URL.createObjectURL(file) : null;
            if (file) this.removeSelected = false;
        }
    }"
>
    <div class="np-pdui-control-card__heading">
        <div>
            <strong>{{ $label }}</strong>
            <small>{{ $hasCustom ? 'Uploaded icon active' : 'Using the built-in icon' }}</small>
        </div>
    </div>

    <div class="np-pdui-icon-field np-bqic-icon-field">
        <div class="np-bqic-icon-preview" aria-hidden="true">
            <img x-show="previewUrl && !removeSelected" x-bind:src="previewUrl || ''" x-cloak alt="" class="np-bqic-icon-image">
            @if($hasCustom)
                <img src="{{ $url }}" alt="" x-show="!previewUrl && !removeSelected" class="np-bqic-icon-image">
            @endif
            <div class="np-bqic-default-icon" x-show="removeSelected || (!previewUrl && {{ $hasCustom ? 'false' : 'true' }})" @if($hasCustom) x-cloak @endif>
                <x-storefront.bulk-quote.icon :name="$fallbackIcon" :size="28" />
            </div>
        </div>

        <div class="np-pdui-icon-content">
            <span class="np-pdui-icon-label">Heading icon · 52 × 52px</span>
            <input
                x-ref="iconFile"
                id="{{ $inputId }}"
                class="sr-only"
                type="file"
                name="icons[{{ $iconKey }}]"
                accept=".png,.jpg,.jpeg,.webp,.avif,image/png,image/jpeg,image/webp,image/avif"
                x-on:change="chooseFile($event)"
                aria-describedby="{{ $inputId }}-help"
                @disabled(!$canManage)
            >
            <div class="np-pdui-icon-actions">
                @if($canManage)
                    <label for="{{ $inputId }}" class="btn btn-white np-pdui-upload-button">Upload icon</label>
                @endif
                <span class="np-pdui-file-name" x-text="fileName || '{{ $hasCustom ? 'Current icon active' : 'No file selected' }}'"></span>
            </div>
            <small class="np-pdui-icon-help" id="{{ $inputId }}-help">PNG, JPG, WebP or AVIF · max 2 MB · transparent display, no cropping</small>
            @if($hasCustom && $canManage)
                <label class="np-pdui-remove-icon np-bqic-restore">
                    <input type="checkbox" name="remove[{{ $iconKey }}]" value="1" x-model="removeSelected" x-on:change="if (removeSelected) { $refs.iconFile.value = ''; fileName = ''; if (previewUrl) URL.revokeObjectURL(previewUrl); previewUrl = null; }">
                    <span>Use default icon instead</span>
                </label>
            @endif
            @error('icons.'.$iconKey)
                <p class="np-bqic-field-error" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </div>
</article>
