@props([
    'label' => 'Image',
    'prefix' => '',
    'image' => null,
    'imageUrl' => null,
    'imageAlt' => null,
    'aspectRatio' => null,
    'aspectTolerance' => 5,
])
@php
    $base = 'image';
    $prefixDot = $prefix ? str_replace(['][', '[', ']'], ['.', '.', ''], $prefix).'.' : '';
    $inputPrefix = $prefix ? $prefix.'[' : '';
    $inputSuffix = $prefix ? ']' : '';
    $field = fn(string $suffix) => $prefix ? $inputPrefix.$base.'_'.$suffix.$inputSuffix : $base.'_'.$suffix;
@endphp
<div class="np-home-admin__media">
    <div class="mb-3 flex items-center justify-between"><strong>{{ $label }}</strong></div>
    @if($image)
        <img src="{{ $image }}" alt="" class="np-home-admin__media-preview mb-3 h-36 w-full rounded-xl object-cover">
    @endif
    <div class="grid gap-4 md:grid-cols-2">
        <label class="np-home-admin__field">
            <span class="np-home-admin__label">Upload / replace</span>
            <input type="file" name="{{ $field('file') }}" accept=".jpg,.jpeg,.png,.webp,.avif,image/*" class="np-home-admin__input">
            <input type="hidden" name="{{ $field('upload_token') }}" value="{{ old($prefixDot.$base.'_upload_token') }}">
            @if($aspectRatio)
                <span class="mt-1.5 block text-xs leading-5 text-slate-500"><strong>Target aspect ratio: {{ $aspectRatio }}</strong> (±{{ $aspectTolerance }}% accepted). Resolution is flexible.</span>
            @endif
        </label>
        <label class="np-home-admin__field"><span class="np-home-admin__label">External image URL</span><input type="text" name="{{ $field('url') }}" value="{{ old($prefixDot.$base.'_url', $imageUrl) }}" class="np-home-admin__input"></label>
        <label class="np-home-admin__field md:col-span-2"><span class="np-home-admin__label">Image alt text</span><input type="text" name="{{ $field('alt') }}" value="{{ old($prefixDot.$base.'_alt', $imageAlt) }}" class="np-home-admin__input"></label>
    </div>
    @if($image)
        <label class="mt-3 inline-flex items-center gap-2 text-sm"><input type="checkbox" name="{{ $prefix ? $inputPrefix.'remove_'.$base.$inputSuffix : 'remove_'.$base }}" value="1"> Remove current image</label>
    @endif
</div>
