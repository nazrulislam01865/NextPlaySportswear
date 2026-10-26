<x-layouts.admin title="Upload Media">
    {{-- Admin soft navigation replaces only #admin-main-content, not the <head>.
         Keep page-specific assets within the swapped content: the existing admin
         navigation executes its scripts before Alpine.initTree(). A normal page
         load also registers this component before the deferred admin bundle starts. --}}
    <link rel="stylesheet" href="{{ asset('css/admin-media-gallery.css') }}?v=20261024c">
    <link rel="stylesheet" href="{{ asset('css/admin-media-upload.css') }}?v=20261026c">
    <script src="{{ asset('js/admin-media-upload.js') }}?v=20261026b"></script>
    <div class="np-mu-page" x-data="adminMediaUploadPrototype({ storeUrl: @js(route('admin.media-library.store')) })">
        <nav class="np-mg-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a><span>›</span><span>Media</span><span>›</span><a href="{{ route('admin.media-library.index') }}">Media Gallery</a><span>›</span><strong>Upload Media</strong>
        </nav>
        <header class="np-mu-heading">
            <div>
                <h1>Upload Media</h1>
                <p>Upload images, documents, and media files to your library. You can upload multiple files at once.</p>
            </div>
            <a href="{{ route('admin.media-library.index') }}" class="btn btn-white np-mu-back">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path d="m13.5 5-7 7 7 7M7 12h13"/></svg>
                Back to Media Gallery
            </a>
        </header>

        <div class="np-mu-layout" :class="{ 'np-mu-layout--single': !selectedFile || !detailsOpen }">
            <section class="np-mu-card np-mu-workspace" aria-label="Upload files">
                <input x-ref="fileInput" type="file" class="sr-only" multiple
                       accept=".jpg,.jpeg,.png,.gif,.webp,.avif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.mp4,.mov,.webm"
                       @change="addFiles($event.target.files)" aria-label="Choose media files">
                <div class="np-mu-dropzone" :class="{ 'is-dragging': dragging }"
                     @dragenter.stop.prevent="startDrag($event)"
                     @dragover.stop.prevent="dragging = isFileDrag($event)"
                     @dragleave.stop.prevent="endDrag($event)"
                     @drop.stop.prevent="drop($event)">
                    <svg class="np-mu-cloud" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 46H14a12 12 0 0 1-1.3-23.9A20 20 0 0 1 52 26a11 11 0 0 1-2 20h-4"/><path d="M32 51V22m-9 9 9-9 9 9"/></svg>
                    <h2>Drag &amp; drop files here</h2>
                    <p>or click to browse from your computer</p>
                    <button type="button" class="btn btn-red np-mu-browse" @click="browse()" :disabled="busy">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 9V5a2 2 0 0 1 2-2h5l2 3"/></svg>
                        Browse Files
                    </button>
                    <small>You can upload multiple files, documents, and videos. Max file size: 20 MB per file.<br>Supported: JPG, JPEG, PNG, GIF, WebP, AVIF, PDF, Office, TXT, MP4, MOV, WebM (SVG excluded for security).</small>
                </div>

                <div class="np-mu-notice np-mu-notice--error" x-cloak x-show="error" role="alert" x-text="error"></div>
                <div class="np-mu-notice np-mu-notice--success" x-cloak x-show="success" role="status">
                    <span x-text="success"></span>
                    <a href="{{ route('admin.media-library.index') }}">View Media Gallery</a>
                </div>

                <div class="np-mu-file-header">
                    <h2>Selected Files (<span x-text="fileCount">0</span>)</h2>
                    <button type="button" class="np-mu-clear" x-show="fileCount" x-cloak :disabled="busy" @click="clearAll()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3m-9 0 1 14h10l1-14M10 11v6m4-6v6"/></svg>
                        Clear All
                    </button>
                </div>

                <div class="np-mu-files" x-show="fileCount" x-cloak>
                    <template x-for="file in files" :key="file.id">
                        <article class="np-mu-file" :class="{ 'is-selected': file.id === selectedId && detailsOpen }" @click="select(file)">
                            <button type="button" class="np-mu-file-identity" @click.stop="select(file)" :aria-pressed="file.id === selectedId && detailsOpen" :aria-label="`Preview ${file.file.name}`">
                                <x-admin.media-upload.preview />
                                <span class="np-mu-file-name-wrap">
                                    <strong x-text="file.file.name" :title="file.file.name"></strong>
                                    <small><span x-text="fileSize(file.file.size)"></span><template x-if="file.width && file.height"><span> • <span x-text="`${file.width} × ${file.height}`"></span></span></template></small>
                                </span>
                            </button>
                            <label class="np-mu-field np-mu-row-field" @click.stop>
                                <span>Title</span>
                                <input type="text" x-model="file.title" maxlength="250" placeholder="Media title" :disabled="busy">
                            </label>
                            <label class="np-mu-field np-mu-row-field" @click.stop>
                                <span>Alt Text</span>
                                <input type="text" x-model="file.altText" maxlength="255" :disabled="busy" :placeholder="file.isImage ? 'Describe the image' : 'Optional'">
                            </label>
                            <button type="button" class="np-mu-remove" title="Remove file" :aria-label="`Remove ${file.file.name}`" @click.stop="remove(file.id)" :disabled="busy">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3m-9 0 1 14h10l1-14M10 11v6m4-6v6"/></svg>
                            </button>
                        </article>
                    </template>
                </div>
                <div class="np-mu-progress" x-cloak x-show="busy" role="status" aria-live="polite">
                    <div class="np-mu-progress-copy">
                        <span x-text="uploadProgressKnown ? (uploadProgress >= 100 ? 'Finishing upload…' : 'Uploading files…') : 'Starting upload…'"></span>
                        <strong x-text="uploadProgressKnown ? `${uploadProgress}%` : 'Preparing…'"></strong>
                    </div>
                    <div class="np-mu-progress-track" role="progressbar" aria-label="Media upload progress" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="uploadProgressKnown ? uploadProgress : null">
                        <span class="np-mu-progress-fill" :class="{ 'is-pending': !uploadProgressKnown }" :style="{ width: uploadProgressKnown ? `${uploadProgress}%` : '30%' }"></span>
                    </div>
                </div>
                <div class="np-mu-actions">
                    <button type="button" class="btn btn-red np-mu-upload" @click="upload()" :disabled="busy || !fileCount" :aria-busy="busy">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 16V3m-5 5 5-5 5 5M4 16v4h16v-4"/></svg>
                        <span x-text="busy ? 'Uploading files…' : `Upload ${fileCount} File${fileCount === 1 ? '' : 's'}`"></span>
                    </button>
                </div>
            </section>

            <aside class="np-mu-card np-mu-details" x-show="selectedFile && detailsOpen" x-cloak aria-label="File Preview and Details">
                <div class="np-mu-details-heading"><h2>File Preview &amp; Details</h2><button type="button" @click="closeDetails()" aria-label="Close file details" title="Close details">×</button></div>
                <template x-if="selectedFile">
                    <div x-data="{ get file() { return selectedFile } }">
                        <div class="np-mu-main-preview"><x-admin.media-upload.preview :large="true" /></div>
                        <div class="np-mu-preview-info"><div><strong x-text="file.file.name"></strong><small><span x-text="fileSize(file.file.size)"></span><template x-if="file.width && file.height"><span> • <span x-text="`${file.width} × ${file.height}`"></span></span></template></small></div><button type="button" @click="window.open(file.previewUrl || '#', '_blank', 'noopener,noreferrer')" x-show="file.previewUrl" aria-label="Open preview in new tab" title="Open preview"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14 4h6v6m0-6-9 9M20 14v6H4V4h6"/></svg></button></div>
                        <label class="np-mu-field"><span>Title</span><input type="text" x-model="file.title" maxlength="250" placeholder="Media title" :disabled="busy"></label>
                        <label class="np-mu-field"><span>Alt Text</span><input type="text" x-model="file.altText" maxlength="255" placeholder="Describe the image" :disabled="busy"></label>
                        <label class="np-mu-field"><span>Caption (optional)</span><textarea x-model="file.caption" maxlength="255" rows="2" :disabled="busy" placeholder="Add a caption"></textarea></label>
                        <label class="np-mu-field"><span>Description (optional)</span><textarea x-model="file.description" maxlength="2000" rows="3" :disabled="busy" placeholder="Add a description"></textarea></label>
                    </div>
                </template>
            </aside>
        </div>
    </div>
</x-layouts.admin>
