@push('admin-page-styles')
<link rel="stylesheet" href="{{ asset('css/admin-media-gallery.css') }}?v=20261024c">
<script src="{{ asset('js/admin-media-gallery.js') }}?v=20261024-16"></script>
@endpush
<x-layouts.admin title="Media Gallery" :media-gallery-header="true">
    <div
        class="np-mg-page"
        x-data="adminMediaGallery({ indexUrl: @js(route('admin.media-library.index')) })"
        x-init="init()"
        @np-gallery-search.window="search = $event.detail; load(1)"
    >
        <div class="np-mg-breadcrumb"><a href="{{ route('admin.dashboard') }}">Dashboard</a><span>›</span><span>Media</span><span>›</span><strong>Media Gallery</strong></div>
        <div class="np-mg-heading">
            <div><h1>Media Gallery</h1><p>Manage all your images, documents and media files in one place.</p></div>
            @if(auth('admin')->user()?->canAdmin('media.manage'))
                <a class="btn btn-red np-mg-upload-button" href="{{ route('admin.media-library.upload') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 16V3m-5 5 5-5 5 5M4 16v4h16v-4"/></svg>
                    Upload Files
                </a>
            @endif
        </div>

        <nav class="np-mg-tabs" aria-label="Media type">
            <template x-for="tab in [{ value: 'all', label: 'All Media' }, { value: 'images', label: 'Images' }, { value: 'documents', label: 'Documents' }, { value: 'videos', label: 'Videos' }, { value: 'others', label: 'Others' }]" :key="tab.value">
                <button type="button" :class="{ 'is-active': type === tab.value }" @click="setType(tab.value)" :aria-current="type === tab.value ? 'page' : null" x-text="tab.label"></button>
            </template>
        </nav>

        <div class="np-mg-filters">
            <label class="np-mg-search"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg><input type="search" x-model="search" @input.debounce.450ms="load(1)" placeholder="Search files by name, type or keyword..." aria-label="Search files"></label>
            <select class="np-mg-select" x-model="type" @change="load(1)" aria-label="File type"><option value="all">All Types</option><option value="images">Images</option><option value="documents">Documents</option><option value="videos">Videos</option><option value="others">Others</option></select>
            <select class="np-mg-select" x-model="date" @change="load(1)" aria-label="Upload date"><option value="all">All Dates</option><option value="today">Today</option><option value="week">Last 7 Days</option><option value="month">This Month</option></select>
            <select class="np-mg-select np-mg-select--category" x-model="category" @change="load(1)" aria-label="Media category"><option value="all">All Categories</option><option value="general">General</option><option value="products">Products</option><option value="branding">Branding</option><option value="banners">Banners</option></select>
            <div class="np-mg-view-switch" role="group" aria-label="Display style"><button type="button" :class="{ 'is-active': view === 'grid' }" @click="view = 'grid'" aria-label="Grid view" :aria-pressed="view === 'grid'"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><rect x="2" y="2" width="9" height="9" rx="1"/><rect x="13" y="2" width="9" height="9" rx="1"/><rect x="2" y="13" width="9" height="9" rx="1"/><rect x="13" y="13" width="9" height="9" rx="1"/></svg></button><button type="button" :class="{ 'is-active': view === 'list' }" @click="view = 'list'" aria-label="List view" :aria-pressed="view === 'list'"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5h12M9 12h12M9 19h12M3 5h2M3 12h2M3 19h2"/></svg></button></div>
        </div>

        <div class="np-mg-notice" x-cloak x-show="notice" role="status" x-text="notice"></div>
        <div class="np-mg-error" x-cloak x-show="error" role="alert"><span x-text="error"></span><button type="button" @click="load(page)">Retry</button></div>

        <div class="np-mg-content" :class="{ 'details-hidden': !detailsOpen || (!selected && !loading) }">
            <div class="np-mg-results">
                <div class="np-mg-grid" :class="{ 'np-mg-grid--list': view === 'list' }" x-show="!loading && items.length" x-cloak>
                    <template x-for="image in items" :key="image.id">
                        <button type="button" class="np-mg-card" :class="{ 'is-selected': selectedId === image.id && detailsOpen }" @click="select(image)" :aria-label="`View details for ${image.filename}`" :aria-pressed="selectedId === image.id && detailsOpen">
                            <div class="np-mg-card-image"><x-admin.media.preview /><span class="np-mg-card-check" aria-hidden="true"><svg x-show="selectedId === image.id && detailsOpen" viewBox="0 0 24 24" width="15" height="15" stroke="white" fill="none" stroke-width="3"><path d="m4 12 5 5 11-11"/></svg></span></div>
                            <div class="np-mg-card-body"><strong x-text="image.filename"></strong><small><span x-text="image.type_label"></span><span class="np-mg-dot">•</span><span x-text="image.size_label"></span></small></div>
                        </button>
                    </template>
                </div>
                <div class="np-mg-grid" x-show="loading" aria-label="Loading media" aria-busy="true">
                    <template x-for="slot in [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16]" :key="slot"><div class="np-mg-card np-mg-placeholder"><span class="np-mg-shimmer"></span><span class="np-mg-placeholder-line"></span><span class="np-mg-placeholder-line np-mg-placeholder-line--short"></span></div></template>
                </div>
                <div class="np-mg-empty" x-show="!loading && !items.length && !error" x-cloak><strong>No media files found</strong><p>Try another filter or upload new media files.</p></div>
                <div class="np-mg-pagination" x-show="!loading && Number(meta.total) > 0" x-cloak>
                    <p>Showing <span x-text="Number(meta.from || 0).toLocaleString()"></span>–<span x-text="Number(meta.to || 0).toLocaleString()"></span> of <span x-text="Number(meta.total || 0).toLocaleString()"></span> files</p>
                    <div class="np-mg-page-buttons" role="navigation" aria-label="Media pages"><button type="button" x-show="page > 1" @click="load(page-1)" aria-label="Previous page">‹</button><template x-for="(entry,index) in pages()" :key="`${entry}-${index}`"><button type="button" :disabled="entry === 'gap'" :class="{ 'is-active': entry === page, 'is-gap': entry === 'gap' }" @click="entry !== 'gap' && load(entry)" x-text="entry === 'gap' ? '…' : entry" :aria-current="entry === page ? 'page' : null"></button></template><button type="button" x-show="page < Number(meta.last_page)" @click="load(page+1)" aria-label="Next page">›</button></div>
                </div>
            </div>

            <template x-if="selected && detailsOpen && !loading">
                <aside class="np-mg-details" aria-label="Selected media details" x-data="{ get image() { return selected; } }">
                    <div class="np-mg-details-image"><x-admin.media.preview :large="true" /><button type="button" class="np-mg-close" @click="closeDetails()" aria-label="Close details">×</button></div>
                    <h2 x-text="image.filename"></h2>
                    <p class="np-mg-details-caption"><span x-text="image.type_label"></span><span>•</span><span x-text="image.size_label"></span><template x-if="image.width && image.height"><span>• <span x-text="`${image.width} × ${image.height}`"></span></span></template></p>
                    <dl class="np-mg-metadata">
                        <div class="np-mg-url-field"><dt>URL</dt><dd><input x-ref="mediaUrl" type="text" readonly :value="new URL(image.url, window.location.origin).toString()" aria-label="Media URL"><button type="button" @click="copyUrl()" aria-label="Copy media URL" title="Copy URL"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/></svg></button></dd></div>
                        <div><dt>File Name</dt><dd x-text="image.filename"></dd></div>
                        <div><dt>File Type</dt><dd x-text="image.type_label"></dd></div>
                        <div><dt>File Size</dt><dd x-text="image.size_label"></dd></div>
                        <div><dt>Dimensions</dt><dd x-text="image.width && image.height ? `${image.width} × ${image.height}` : '—'"></dd></div>
                        <div><dt>Upload Date</dt><dd x-text="displayDate(image)"></dd></div>
                        <div x-show="editing"><dt>Category</dt><dd><select class="np-mg-meta-input" x-model="form.category"><option value="general">General</option><option value="products">Products</option><option value="branding">Branding</option><option value="banners">Banners</option></select></dd></div>
                        <div><dt>Alt Text</dt><dd><input class="np-mg-meta-input" maxlength="255" type="text" x-model="form.alt_text" :readonly="!editing" aria-label="Alt Text"></dd></div>
                        <div><dt>Caption</dt><dd><input class="np-mg-meta-input" maxlength="255" type="text" x-model="form.caption" :readonly="!editing" aria-label="Caption"></dd></div>
                        <div class="np-mg-description"><dt>Description</dt><dd><textarea class="np-mg-meta-input" maxlength="2000" rows="3" x-model="form.description" :readonly="!editing" aria-label="Description"></textarea></dd></div>
                    </dl>
                    <div class="np-mg-actions">
                        @if(auth('admin')->user()?->canAdmin('media.manage'))
                            <button type="button" class="btn btn-red" x-show="!editing" @click="editing = true">Edit</button>
                            <button type="button" class="btn btn-red" x-show="editing" :disabled="saving" @click="save()" x-text="saving ? 'Saving…' : 'Save'" x-cloak></button>
                            <button type="button" class="btn btn-white" x-show="editing" @click="syncForm()" x-cloak>Cancel</button>
                        @endif
                        <a class="btn btn-white np-mg-download" :href="image.download_url" download><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v12m-5-5 5 5 5-5M4 17v4h16v-4"/></svg>Download</a>
                        @if(auth('admin')->user()?->canAdmin('media.manage'))
                            <button type="button" class="btn np-mg-delete" :disabled="deleting" @click="remove()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M10 7V4h4v3M6 7l1 14h10l1-14M10 11v6M14 11v6"/></svg><span x-text="deleting ? 'Deleting…' : 'Delete'"></span></button>
                        @endif
                    </div>
                </aside>
            </template>
        </div>
    </div>
</x-layouts.admin>
