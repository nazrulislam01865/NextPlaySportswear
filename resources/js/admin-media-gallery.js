// NextPlay media archive. A single paginated request per view; no infinite image accumulation.
window.adminMediaGallery = (initial = {}) => ({
    indexUrl: initial.indexUrl,
    type: 'all',
    date: 'all',
    category: 'all',
    search: '',
    view: 'grid',
    page: 1,
    perPage: 16,
    meta: { current_page: 1, last_page: 1, total: 0, from: 0, to: 0 },
    items: [],
    selectedId: null,
    detailsOpen: true,
    editing: false,
    form: { alt_text: '', caption: '', description: '', category: 'general' },
    loading: true,
    saving: false,
    deleting: false,
    error: '',
    notice: '',
    requestController: null,
    init() {
        this.load(1);
    },
    get selected() {
        return this.items.find(item => Number(item.id) === Number(this.selectedId)) || null;
    },
    displayDate(item) {
        if (!item?.created_at_iso) return item?.created_at || '—';
        const date = new Date(item.created_at_iso);
        return Number.isNaN(date.getTime()) ? (item.created_at || '—') : date.toLocaleString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    },
    csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    },
    queryUrl(page) {
        const url = new URL(this.indexUrl, window.location.origin);
        const params = { page, per_page: this.perPage, type: this.type, date: this.date, category: this.category };
        for (const [key, value] of Object.entries(params)) url.searchParams.set(key, String(value));
        if (this.search.trim()) url.searchParams.set('q', this.search.trim());
        return url.toString();
    },
    async load(page = 1) {
        this.requestController?.abort();
        const controller = new AbortController();
        this.requestController = controller;
        this.loading = true;
        this.error = '';
        this.notice = '';
        try {
            const response = await fetch(this.queryUrl(page), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: controller.signal,
            });
            if (!response.ok) throw new Error(response.status === 422 ? 'Check your search or filters.' : 'Media could not be loaded. Please retry.');
            const payload = await response.json();
            if (this.requestController !== controller) return;
            this.items = Array.isArray(payload.data) ? payload.data : [];
            this.meta = payload.meta || this.meta;
            this.page = Number(this.meta.current_page || page);
            // Keep the selected image only if it still belongs to this page.
            this.selectedId = this.items.some(item => Number(item.id) === Number(this.selectedId))
                ? this.selectedId : (this.items[0]?.id ?? null);
            this.detailsOpen = Boolean(this.selectedId);
            this.syncForm();
        } catch (error) {
            if (error?.name !== 'AbortError' && this.requestController === controller) {
                this.error = error?.message || 'Media could not be loaded. Please retry.';
            }
        } finally {
            if (this.requestController === controller) {
                this.loading = false;
                this.requestController = null;
            }
        }
    },
    setType(type) {
        if (this.type === type) return;
        this.type = type;
        this.load(1);
    },
    select(item) {
        if (!item) return;
        this.selectedId = item.id;
        this.detailsOpen = true;
        this.editing = false;
        this.syncForm();
    },
    closeDetails() {
        this.detailsOpen = false;
        this.editing = false;
    },
    syncForm() {
        const item = this.selected;
        this.editing = false;
        this.form = {
            alt_text: item?.alt_text || '',
            caption: item?.caption || '',
            description: item?.description || '',
            category: item?.category || 'general',
        };
    },
    pages() {
        const last = Math.max(1, Number(this.meta.last_page || 1));
        const page = Number(this.page || 1);
        const pages = new Set([1, last]);
        const start = Math.max(1, Math.min(page - 2, last - 4));
        for (let number = start; number <= Math.min(last, start + 4); number += 1) pages.add(number);
        const result = [...pages].sort((a, b) => a - b);
        return result.flatMap((number, index) => index && number - result[index - 1] > 1 ? ['gap', number] : [number]);
    },
    async mutate(url, method, body) {
        const response = await fetch(url, {
            method,
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
            credentials: 'same-origin',
            body: body ? JSON.stringify(body) : undefined,
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || `Request failed (${response.status}).`);
        return payload;
    },
    async save() {
        const item = this.selected;
        if (!item || this.saving) return;
        this.saving = true;
        this.error = '';
        this.notice = '';
        try {
            const payload = await this.mutate(item.update_url, 'PATCH', this.form);
            this.items = this.items.map(row => row.id === item.id ? payload.data : row);
            this.syncForm();
            this.notice = payload.message || 'Media details saved.';
        } catch (error) {
            this.error = error?.message || 'Unable to save media details.';
        } finally {
            this.saving = false;
        }
    },
    async remove() {
        const item = this.selected;
        if (!item || this.deleting || !window.confirm(`Delete "${item.filename}" from the gallery?`)) return;
        this.deleting = true;
        this.error = '';
        this.notice = '';
        try {
            const payload = await this.mutate(item.delete_url, 'DELETE');
            this.selectedId = null;
            await this.load(this.items.length === 1 && this.page > 1 ? this.page - 1 : this.page);
            this.notice = payload.message || 'File removed.';
        } catch (error) {
            this.error = error?.message || 'Unable to delete this media file.';
        } finally {
            this.deleting = false;
        }
    },
    async copyUrl() {
        const url = this.selected?.url;
        if (!url) return;
        try {
            await navigator.clipboard.writeText(new URL(url, window.location.origin).toString());
            this.notice = 'Media URL copied.';
        } catch {
            this.notice = 'Select and copy the URL field.';
            this.$refs.mediaUrl?.select();
        }
    },
});

// Standalone archive upload page. Compose the existing shared media manager;
// leave the product editor's image-only upload flow untouched.
window.adminMediaUploadPage = (initial = {}) => {
    const manager = window.adminMediaLibraryManager({ ...initial, allowFiles: true });
    manager.handleScroll = () => {};
    manager.fillScrollableArea = () => {};
    manager.upload = async function (fileList) {
        const files = Array.from(fileList || []);
        if (!files.length || this.uploadBusy) return;
        const images = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
        const documents = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
        const videos = ['mp4', 'webm'];
        const invalid = files.find(file => {
            const extension = String(file.name || '').split('.').pop().toLowerCase();
            const limit = videos.includes(extension) ? 30 : documents.includes(extension) ? 10 : 5;
            return ![...images, ...documents, ...videos].includes(extension) || file.size > limit * 1024 * 1024;
        });
        if (files.length > 20 || invalid) {
            this.uploadError = 'Maximum 20 files. JPG/PNG/WebP/AVIF: 5 MB; documents: 10 MB; MP4/WebM: 30 MB.';
            return;
        }
        const data = new FormData();
        files.forEach(file => data.append('files[]', file));
        this.uploadBusy = true;
        this.uploadError = '';
        try {
            const response = await fetch(this.storeUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this.csrfToken() },
                credentials: 'same-origin',
                body: data,
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(payload.message || Object.values(payload.errors || {}).flat()[0] || 'Unable to upload files.');
            }
            this.items = [...(Array.isArray(payload.data) ? payload.data : []), ...this.items].slice(0, this.perPage);
            this.totalImages += Number(payload.data?.length || 0);
        } catch (error) {
            this.uploadError = error?.message || 'Unable to upload files.';
        } finally {
            this.uploadBusy = false;
            if (this.$refs.uploadInput) this.$refs.uploadInput.value = '';
        }
    };
    return manager;
};
