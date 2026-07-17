<x-layouts.admin
    title="Image Gallery"
    subtitle="Upload, search, and reuse global product images from one admin page."
>
    <div
        x-data="adminMediaLibraryManager({
            indexUrl: @js(route('admin.media-library.index')),
            storeUrl: @js(route('admin.media-library.store')),
            totalImages: @js((int) $totalImages),
        })"
        x-init="init()"
        class="space-y-5"
    >
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
                <div class="flex items-center gap-4">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-blue-50 text-lg font-black text-brand-blue">▧</span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold leading-4 text-slate-600">Total Images</p>
                        <p class="mt-1 text-2xl font-black leading-none text-brand-ink" x-text="totalImages.toLocaleString()">{{ number_format((int) $totalImages) }}</p>
                        <p class="mt-1 truncate text-[11px] font-medium text-slate-400">Reusable media library files</p>
                    </div>
                </div>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:col-span-1 xl:col-span-2">
                <div class="flex items-center gap-4">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-50 text-lg font-black text-emerald-700">✓</span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold leading-4 text-slate-600">Latest Upload</p>
                        <p class="mt-1 truncate text-lg font-black leading-tight text-brand-ink">{{ $latestImage?->name ?? 'No images uploaded yet' }}</p>
                        <p class="mt-1 truncate text-[11px] font-medium text-slate-400">{{ $latestImage?->created_at?->format('M j, Y g:i A') ?? 'Upload images below to start the gallery.' }}</p>
                    </div>
                </div>
            </article>
        </div>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <label class="admin-label min-w-0 flex-1">
                        Search Gallery
                        <input class="admin-input" type="search" x-model="search" @input.debounce.450ms="load(false)" placeholder="Search by image name or alt text" autocomplete="off">
                    </label>

                    <div class="grid gap-3 sm:grid-cols-[minmax(240px,1fr)_auto] xl:min-w-[520px]">
                        <input x-ref="uploadInput" class="sr-only" type="file" multiple accept="image/jpeg,image/png,image/webp,image/avif" @change="upload($event.target.files)">
                        <div
                            class="np-gallery-page-upload"
                            :class="dragging ? 'is-dragging' : ''"
                            role="button"
                            tabindex="0"
                            aria-label="Upload images to gallery"
                            @click="$refs.uploadInput?.click()"
                            @keydown.enter.prevent="$refs.uploadInput?.click()"
                            @keydown.space.prevent="$refs.uploadInput?.click()"
                            @dragenter.stop.prevent="startDrag($event)"
                            @dragover.stop.prevent="dragging = isFileDrag($event)"
                            @dragleave.stop.prevent="endDrag($event)"
                            @drop.stop.prevent="drop($event)"
                        >
                            <strong x-text="uploadBusy ? 'Uploading…' : (dragging ? 'Drop images here' : 'Upload Images')"></strong>
                            <small>JPG, PNG, WebP or AVIF. Max 20 files, 5 MB each.</small>
                        </div>
                        <button type="button" class="btn btn-navy h-full min-h-12" @click="$refs.uploadInput?.click()">+ Select Files</button>
                    </div>
                </div>

                <p class="np-media-alert np-media-alert--error !mx-0" x-show="error" x-text="error"></p>
                <p class="np-media-alert np-media-alert--error !mx-0" x-show="uploadError" x-text="uploadError"></p>
            </div>

            <div
                class="np-gallery-page-grid"
                x-ref="scroller"
                x-show="items.length"
                @scroll.passive="handleScroll($event)"
            >
                <template x-for="image in items" :key="image.id">
                    <article class="np-gallery-page-card" x-show="!image.broken">
                        <a :href="image.url" target="_blank" rel="noopener" class="block" :aria-label="`Open ${image.name || 'gallery image'}`">
                            <img :src="image.url" :alt="image.alt_text || image.name" loading="lazy" x-on:error="image.broken = true">
                        </a>
                        <div class="min-w-0 p-3">
                            <strong x-text="image.name || 'Gallery image'"></strong>
                            <small x-text="image.size_label || image.created_at || 'Reusable image'"></small>
                        </div>
                    </article>
                </template>
            </div>

            <div class="np-media-empty" x-show="!loading && !items.length">
                No gallery images found. Upload images above to start the global image gallery.
            </div>

            <div class="border-t border-slate-200 p-4 text-center">
                <div class="np-media-loading !m-0" x-show="loading">Loading images…</div>
                <button type="button" class="btn btn-white" x-show="hasMore && !loading" @click="load(true)">Load more images</button>
                <p class="text-sm font-bold text-slate-500" x-show="!hasMore && items.length && !loading">All gallery images loaded.</p>
            </div>
        </section>
    </div>
</x-layouts.admin>
