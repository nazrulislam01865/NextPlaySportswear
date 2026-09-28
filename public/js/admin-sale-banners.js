window.adminSaleBannerEditor = (initial = {}) => ({
    name: String(initial.name || ''),
    campaignId: String(initial.campaignId || ''),
    desktopPreviewUrl: String(initial.desktopImageUrl || ''),
    mobilePreviewUrl: String(initial.mobileImageUrl || ''),
    altText: String(initial.altText || ''),
    heading: String(initial.heading || ''),
    ctaLabel: String(initial.ctaLabel || ''),
    destinationLink: String(initial.destinationLink || '/sale'),
    placements: Array.isArray(initial.placements) ? [...initial.placements] : ['sale_top'],
    priority: Math.max(1, Number(initial.priority || 1)),
    inheritSchedule: initial.inheritSchedule !== false,
    timezone: String(initial.timezone || 'Europe/London'),
    startDate: String(initial.startDate || ''),
    startTime: String(initial.startTime || '00:00'),
    endDate: String(initial.endDate || ''),
    endTime: String(initial.endTime || '23:59'),
    previewDevice: 'desktop',
    removeMobile: false,
    desktopBlobUrl: '',
    mobileBlobUrl: '',

    setDesktopFile(file) {
        if (!file) return;
        if (this.desktopBlobUrl) URL.revokeObjectURL(this.desktopBlobUrl);
        this.desktopBlobUrl = URL.createObjectURL(file);
        this.desktopPreviewUrl = this.desktopBlobUrl;
    },

    clearDesktopFile() {
        if (this.desktopBlobUrl) URL.revokeObjectURL(this.desktopBlobUrl);
        this.desktopBlobUrl = '';
        if (this.$refs.desktopInput) this.$refs.desktopInput.value = '';
        this.desktopPreviewUrl = String(initial.desktopImageUrl || '');
    },

    setMobileFile(file) {
        if (!file) return;
        if (this.mobileBlobUrl) URL.revokeObjectURL(this.mobileBlobUrl);
        this.mobileBlobUrl = URL.createObjectURL(file);
        this.mobilePreviewUrl = this.mobileBlobUrl;
        this.removeMobile = false;
    },

    clearMobileFile() {
        if (this.mobileBlobUrl) URL.revokeObjectURL(this.mobileBlobUrl);
        this.mobileBlobUrl = '';
        this.mobilePreviewUrl = '';
        this.removeMobile = true;
        if (this.$refs.mobileInput) this.$refs.mobileInput.value = '';
    },

    togglePlacement(value, checked) {
        if (checked && !this.placements.includes(value)) this.placements.push(value);
        if (!checked) this.placements = this.placements.filter(item => item !== value);
    },

    get previewImageUrl() {
        if (this.previewDevice === 'mobile') return this.mobilePreviewUrl || this.desktopPreviewUrl;
        return this.desktopPreviewUrl;
    },

    get previewUrl() {
        const value = this.destinationLink.trim();
        if (!value) return '/sale';
        return value;
    },
});

(() => {
    const list = document.querySelector('[data-sale-banner-sort-list]');
    if (!list) return;

    let dragged = null;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    list.querySelectorAll('[data-sale-banner-id]').forEach((item) => {
        item.addEventListener('dragstart', () => {
            dragged = item;
            item.classList.add('is-dragging');
        });
        item.addEventListener('dragend', () => {
            item.classList.remove('is-dragging');
            dragged = null;
            saveOrder();
        });
    });

    list.addEventListener('dragover', (event) => {
        event.preventDefault();
        if (!dragged) return;
        const siblings = [...list.querySelectorAll('[data-sale-banner-id]:not(.is-dragging)')];
        const next = siblings.find((item) => event.clientY <= item.getBoundingClientRect().top + item.offsetHeight / 2);
        if (next) list.insertBefore(dragged, next);
        else list.appendChild(dragged);
    });

    async function saveOrder() {
        const ids = [...list.querySelectorAll('[data-sale-banner-id]')].map((item) => Number(item.dataset.saleBannerId));
        if (!ids.length) return;
        try {
            await fetch(list.dataset.reorderUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ ids }),
            });
        } catch (error) {
            // Ordering will remain visually changed until refresh; no destructive fallback.
        }
    }
})();
