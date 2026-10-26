@props(['large' => false])
{{-- Shared preview for the selected-file rows and the details inspector. --}}
<div class="np-mu-preview {{ $large ? 'np-mu-preview--large' : '' }}">
    <template x-if="file.isImage && file.previewUrl && !file.previewFailed">
        <img :src="file.previewUrl" :alt="file.altText || file.file.name" decoding="async">
    </template>
    <template x-if="!file.isImage || file.previewFailed">
        <span class="np-mu-preview-fallback" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M13 2v7h7M8 15h8M8 18h5"/></svg>
            <span x-text="file.isVideo ? 'VIDEO' : (file.file.name.split('.').pop() || 'FILE').toUpperCase()"></span>
        </span>
    </template>
</div>
