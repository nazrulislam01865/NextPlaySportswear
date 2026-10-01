(function () {
    'use strict';

    // Product-detail lightbox only. Keep the preview deliberately simple:
    // use the exact same image URL that already works in the product gallery,
    // render the image immediately, and let CSS size/center the real image box.
    window.productImageViewerV4 = function () {
        return {
            imageOpen: false,
            image: null,
            previewSrc: '',
            returnFocus: null,

            open(image) {
                const src = String(image?.url || '').trim();
                if (!src) return;

                if (!this.imageOpen) this.returnFocus = document.activeElement;

                this.image = image;
                this.previewSrc = src;
                this.imageOpen = true;
                document.documentElement.classList.add('np-product-preview-open');

                this.$nextTick(() => {
                    if (this.imageOpen) this.$refs.previewClose?.focus({ preventScroll: true });
                });
            },

            close() {
                if (!this.imageOpen) return;

                this.imageOpen = false;
                this.image = null;
                this.previewSrc = '';
                document.documentElement.classList.remove('np-product-preview-open');

                if (this.returnFocus?.isConnected) {
                    this.returnFocus.focus({ preventScroll: true });
                }

                this.returnFocus = null;
            },
        };
    };
}());
