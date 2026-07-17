<x-layouts.admin
    title="Upload Media File"
    subtitle="Upload reusable product and content images to the global media gallery."
>
    <div
        x-data="adminMediaLibraryManager({
            indexUrl: @js(route('admin.media-library.index')),
            storeUrl: @js(route('admin.media-library.store')),
            totalImages: @js((int) $totalImages),
            scope: 'today',
            perPage: 24,
            emptyMessage: 'No media files were uploaded today. New uploads will appear here immediately.',
        })"
        x-init="init()"
        class="space-y-5"
    >
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
                <div class="flex items-center gap-4">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-blue-50 text-lg font-black text-brand-blue">▧</span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold leading-4 text-slate-600">Total Media Files</p>
                        <p class="mt-1 text-2xl font-black leading-none text-brand-ink" x-text="totalImages.toLocaleString()">{{ number_format((int) $totalImages) }}</p>
                        <p class="mt-1 truncate text-[11px] font-medium text-slate-400">Reusable files available in Gallery</p>
                    </div>
                </div>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:col-span-1 xl:col-span-2">
                <div class="flex items-center gap-4">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-50 text-lg font-black text-emerald-700">✓</span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold leading-4 text-slate-600">Latest Upload</p>
                        <p class="mt-1 truncate text-lg font-black leading-tight text-brand-ink">{{ $latestImage?->name ?? 'No media uploaded yet' }}</p>
                        <p class="mt-1 truncate text-[11px] font-medium text-slate-400">{{ $latestImage?->created_at?->format('M j, Y g:i A') ?? 'Upload media files below to start the gallery.' }}</p>
                    </div>
                </div>
            </article>
        </div>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Media</p>
                        <h2 class="mt-2 text-2xl font-black text-brand-ink">Upload Media File</h2>
                        <p class="mt-2 text-sm font-semibold leading-6 text-slate-500">Drag files here or choose images from your device. Uploaded files will be available in the Gallery and product image picker.</p>
                    </div>
                    <a href="{{ route('admin.media-library.index') }}" class="btn btn-white">View Gallery</a>
                </div>
            </div>

            <div class="p-5">
                <input x-ref="uploadInput" class="sr-only" type="file" multiple accept="image/jpeg,image/png,image/webp,image/avif" @change="upload($event.target.files)">
                <div
                    class="np-gallery-page-upload min-h-[260px]"
                    :class="dragging ? 'is-dragging' : ''"
                    role="button"
                    tabindex="0"
                    aria-label="Upload media files"
                    @click="$refs.uploadInput?.click()"
                    @keydown.enter.prevent="$refs.uploadInput?.click()"
                    @keydown.space.prevent="$refs.uploadInput?.click()"
                    @dragenter.stop.prevent="startDrag($event)"
                    @dragover.stop.prevent="dragging = isFileDrag($event)"
                    @dragleave.stop.prevent="endDrag($event)"
                    @drop.stop.prevent="drop($event)"
                >
                    <strong class="text-lg" x-text="uploadBusy ? 'Uploading…' : (dragging ? 'Drop media files here' : 'Upload media files')"></strong>
                    <small>JPG, PNG, WebP or AVIF. Max 20 files, 5 MB each.</small>
                    <span class="mt-5 inline-flex rounded-full bg-brand-red px-5 py-3 text-sm font-black text-white shadow-lg shadow-red-100">+ Select Files</span>
                </div>

                <p class="np-media-alert np-media-alert--error !mx-0" x-show="uploadError" x-text="uploadError"></p>
                <p class="np-media-alert np-media-alert--error !mx-0" x-show="error" x-text="error"></p>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="flex flex-col gap-3 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-black text-brand-ink">Today's Uploaded Media</h2>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Only files uploaded today are shown here. Use Gallery to browse all media.</p>
                </div>
                <label class="admin-label mb-0 w-full sm:max-w-xs">
                    Search today's uploads
                    <input class="admin-input" type="search" x-model="search" @input.debounce.450ms="load(false)" placeholder="Search today's media" autocomplete="off">
                </label>
            </div>

            <div
                class="np-gallery-page-grid"
                x-ref="scroller"
                x-show="items.length"
                @scroll.passive="handleScroll($event)"
            >
                <template x-for="image in items" :key="image.id">
                    <article class="np-gallery-page-card" x-show="!image.broken">
                        <a :href="image.url" target="_blank" rel="noopener" class="block" :aria-label="`Open ${image.name || 'media file'}`">
                            <img :src="image.url" :alt="image.alt_text || image.name" loading="lazy" x-on:error="image.broken = true">
                        </a>
                        <div class="min-w-0 p-3">
                            <strong x-text="image.name || 'Media file'"></strong>
                            <small x-text="image.size_label || image.created_at || 'Reusable media file'"></small>
                        </div>
                    </article>
                </template>
            </div>

            <div class="np-media-empty" x-show="!loading && !items.length">
                <span x-text="emptyMessage">No media files were uploaded today.</span>
            </div>

            <div class="border-t border-slate-200 p-4 text-center">
                <div class="np-media-loading !m-0" x-show="loading">Loading media…</div>
                <button type="button" class="btn btn-white" x-show="hasMore && !loading" @click="load(true)">Load more media</button>
                <p class="text-sm font-bold text-slate-500" x-show="!hasMore && items.length && !loading">All today's uploaded media loaded.</p>
            </div>
        </section>
    </div>
</x-layouts.admin>
