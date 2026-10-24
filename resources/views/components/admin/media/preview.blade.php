@props(['large' => false])
{{-- Shared thumbnail/preview. The loader uses the canonical product-card shimmer behavior. --}}
<div
    class="np-mg-media {{ $large ? 'np-mg-media--large' : '' }}"
    x-data="{ currentSrc: '', loaded: false, failed: false }"
    x-effect="if (currentSrc !== (image?.url || '')) { currentSrc = image?.url || ''; loaded = false; failed = false; }"
>
    <template x-if="image.type === 'images'">
        <div class="np-mg-image-wrap" :class="{ 'is-loaded': loaded }">
            <span class="np-mg-shimmer" aria-hidden="true" x-show="!loaded"></span>
            <img
                :src="image.url"
                :alt="image.alt_text || image.filename || ''"
                {{ $large ? 'loading=eager' : 'loading=lazy' }}
                decoding="async"
                x-on:load="loaded = true"
                x-on:error="loaded = true; failed = true"
                x-show="!failed"
            >
            <span x-show="failed" class="np-mg-media-fallback">Image unavailable</span>
        </div>
    </template>
    <template x-if="image.type === 'videos'">
        <div class="np-mg-file-symbol" :class="{ 'np-mg-file-symbol--large': {{ $large ? 'true' : 'false' }} }">
            @if($large)
                <video controls preload="none" playsinline :src="image.preview_url" :aria-label="image.filename"></video>
            @else
                <span class="np-mg-file-symbol-glyph" aria-hidden="true">▶</span><span>VIDEO</span>
            @endif
        </div>
    </template>
    <template x-if="image.type !== 'images' && image.type !== 'videos'">
        <div class="np-mg-file-symbol" :class="{ 'np-mg-file-symbol--large': {{ $large ? 'true' : 'false' }} }">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M13 2v7h7M8 14h8M8 18h6"/></svg>
            <span x-text="image.type_label || 'FILE'"></span>
        </div>
    </template>
    <span class="np-mg-corner-icon" aria-hidden="true" x-show="image.type === 'images'">
        <svg viewBox="0 0 24 24" width="17" height="17" fill="currentColor" aria-hidden="true"><path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2ZM8.5 7a2 2 0 1 1 0 4 2 2 0 0 1 0-4ZM5 18l4.5-5.5 3 3L16 11l5 7Z"/></svg>
    </span>
</div>
