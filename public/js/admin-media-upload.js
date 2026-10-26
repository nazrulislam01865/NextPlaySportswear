// NextPlay upload queue: client-side previews only; one request when Upload is pressed.
// Independent of the shared product image picker and gallery search.
window.adminMediaUploadPrototype = (options = {}) => ({
    storeUrl: options.storeUrl,
    files: [],
    selectedId: null,
    detailsOpen: true,
    dragging: false,
    dragDepth: 0,
    busy: false,
    uploadProgress: 0,
    uploadProgressKnown: false,
    request: null,
    error: '',
    success: '',
    nextId: 1,
    get selectedFile() { return this.files.find(item => item.id === this.selectedId) || null; },
    get fileCount() { return this.files.length; },
    get acceptFormats() { return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'mp4', 'mov', 'webm']; },
    isFileDrag(event) { return Array.from(event?.dataTransfer?.types || []).includes('Files'); },
    startDrag(event) { if (this.isFileDrag(event) && !this.busy) { this.dragDepth += 1; this.dragging = true; } },
    endDrag(event) { if (this.isFileDrag(event)) { this.dragDepth = Math.max(0, this.dragDepth - 1); this.dragging = this.dragDepth > 0; } },
    drop(event) {
        event.preventDefault();
        this.dragDepth = 0;
        this.dragging = false;
        if (!this.busy) this.addFiles(event.dataTransfer?.files);
    },
    browse() { if (!this.busy) this.$refs.fileInput?.click(); },
    fileSize(bytes) { return bytes >= 1048576 ? `${(bytes / 1048576).toFixed(2)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`; },
    addFiles(fileList) {
        if (this.busy) return;
        this.error = '';
        this.success = '';
        const incoming = Array.from(fileList || []);
        if (!incoming.length) return;
        if (incoming.length + this.files.length > 20) {
            this.error = 'You can select up to 20 files at a time. Remove some files and try again.';
            return;
        }
        const invalid = incoming.find(file => {
            const ext = String(file.name || '').split('.').pop().toLowerCase();
            return !this.acceptFormats.includes(ext) || file.size <= 0 || file.size > 20 * 1024 * 1024;
        });
        if (invalid) {
            this.error = `“${invalid.name}” is not a supported file or exceeds 20 MB. SVG files are not supported for security.`;
            return;
        }
        const existing = new Set(this.files.map(item => `${item.file.name}|${item.file.size}|${item.file.lastModified}`));
        let lastAdded = null;
        for (const file of incoming) {
            const identity = `${file.name}|${file.size}|${file.lastModified}`;
            if (existing.has(identity)) continue;
            existing.add(identity);
            const extension = file.name.split('.').pop().toLowerCase();
            const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'].includes(extension);
            const isVideo = ['mp4', 'mov', 'webm'].includes(extension);
            const originalTitle = file.name.replace(/\.[^.]+$/, '').replace(/[_-]+/g, ' ').trim();
            const entry = {
                id: this.nextId++, file, isImage, isVideo,
                previewUrl: isImage ? URL.createObjectURL(file) : null, previewFailed: false,
                title: originalTitle.substring(0, 250), altText: isImage ? originalTitle.substring(0, 255) : '',
                caption: '', description: '', width: null, height: null,
            };
            this.files.push(entry);
            if (isImage) {
                const image = new Image();
                image.onload = () => { const current = this.files.find(item => item.id === entry.id); if (current) { current.width = image.naturalWidth; current.height = image.naturalHeight; } };
                image.onerror = () => { const current = this.files.find(item => item.id === entry.id); if (current) current.previewFailed = true; };
                image.src = entry.previewUrl;
            }
            lastAdded = entry;
        }
        if (lastAdded) {
            this.selectedId = this.selectedId && this.files.some(item => item.id === this.selectedId) ? this.selectedId : lastAdded.id;
            this.detailsOpen = true;
        }
        if (this.$refs.fileInput) this.$refs.fileInput.value = '';
    },
    select(entry) { this.selectedId = entry.id; this.detailsOpen = true; },
    closeDetails() { this.detailsOpen = false; },
    remove(id) {
        if (this.busy) return;
        const index = this.files.findIndex(item => item.id === id);
        if (index < 0) return;
        const [entry] = this.files.splice(index, 1);
        if (entry.previewUrl) URL.revokeObjectURL(entry.previewUrl);
        if (this.selectedId === id) this.selectedId = this.files[Math.min(index, this.files.length - 1)]?.id ?? null;
        this.error = '';
    },
    clearAll() {
        if (this.busy) return;
        for (const entry of this.files) if (entry.previewUrl) URL.revokeObjectURL(entry.previewUrl);
        this.files = [];
        this.selectedId = null;
        this.error = '';
        if (this.$refs.fileInput) this.$refs.fileInput.value = '';
    },
    async upload() {
        if (this.busy || !this.files.length) return;
        this.error = '';
        this.success = '';
        this.uploadProgress = 0;
        this.uploadProgressKnown = false;
        const data = new FormData();
        this.files.forEach((entry, index) => {
            data.append('files[]', entry.file);
            data.append(`metadata[${index}][title]`, entry.title.trim());
            data.append(`metadata[${index}][alt_text]`, entry.altText.trim());
            data.append(`metadata[${index}][caption]`, entry.caption.trim());
            data.append(`metadata[${index}][description]`, entry.description.trim());
        });
        this.busy = true;
        try {
            // XMLHttpRequest provides real upload-byte progress (fetch does not).
            // Preserve the same single Laravel request and JSON response contract.
            const response = await new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                this.request = xhr;
                xhr.open('POST', this.storeUrl);
                xhr.withCredentials = true;
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.content || '');
                xhr.upload.addEventListener('progress', event => {
                    if (!event.lengthComputable || event.total <= 0) return;
                    this.uploadProgressKnown = true;
                    this.uploadProgress = Math.min(100, Math.max(0, Math.round(event.loaded / event.total * 100)));
                });
                xhr.addEventListener('load', () => {
                    let payload = {};
                    try { payload = JSON.parse(xhr.responseText || '{}'); } catch (_) { /* Server may return an HTML error page. */ }
                    resolve({ ok: xhr.status >= 200 && xhr.status < 300, status: xhr.status, payload });
                });
                xhr.addEventListener('error', () => reject(new Error('Upload failed. Check your connection and retry.')));
                xhr.addEventListener('abort', () => reject(new Error('Upload was cancelled.')));
                try { xhr.send(data); } catch (error) { reject(error); }
            });
            const payload = response.payload;
            if (!response.ok) {
                const validation = Object.values(payload.errors || {}).flat()[0];
                throw new Error(response.status === 413
                    ? 'The server rejected the upload size. Ask your administrator to increase PHP post_max_size and upload_max_filesize.'
                    : response.status === 419
                        ? 'Your session has expired. Refresh the page and try again.'
                        : (validation || payload.message || `Unable to upload files (${response.status}).`));
            }
            this.uploadProgressKnown = true;
            this.uploadProgress = 100;
            const count = Array.isArray(payload.data) ? payload.data.length : 0;
            this.clearUploadedFiles();
            this.success = payload.message || `${count} file${count === 1 ? '' : 's'} added to your media gallery.`;
        } catch (error) {
            this.error = error instanceof Error ? error.message : 'Upload failed. Check your connection and retry.';
        } finally {
            this.busy = false;
            this.request = null;
        }
    },
    clearUploadedFiles() {
        for (const entry of this.files) if (entry.previewUrl) URL.revokeObjectURL(entry.previewUrl);
        this.files = [];
        this.selectedId = null;
        if (this.$refs.fileInput) this.$refs.fileInput.value = '';
    },
    destroy() { if (this.request && this.busy) this.request.abort(); this.clearUploadedFiles(); },
});
