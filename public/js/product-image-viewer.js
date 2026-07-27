(function () {
    'use strict';

    const previewCache = new Map();

    const loadImage = (url) => new Promise((resolve, reject) => {
        const image = new Image();
        image.decoding = 'async';

        try {
            const parsed = new URL(url, window.location.href);
            if (parsed.origin !== window.location.origin) image.crossOrigin = 'anonymous';
        } catch (error) {
            // Relative and data URLs do not require a cross-origin mode.
        }

        image.onload = () => resolve(image);
        image.onerror = () => reject(new Error('Product image could not be loaded.'));
        image.src = url;
    });

    const canvasToObjectUrl = (canvas) => new Promise((resolve, reject) => {
        canvas.toBlob((blob) => {
            if (!blob) {
                reject(new Error('Product image could not be prepared.'));
                return;
            }

            resolve(URL.createObjectURL(blob));
        }, 'image/png');
    });

    const trimOuterWhitespace = async (url) => {
        if (!url) return '';
        if (previewCache.has(url)) return previewCache.get(url);

        const image = await loadImage(url);
        const sourceWidth = image.naturalWidth || image.width;
        const sourceHeight = image.naturalHeight || image.height;

        if (!sourceWidth || !sourceHeight) return url;

        const analysisLimit = 1400;
        const analysisScale = Math.min(1, analysisLimit / Math.max(sourceWidth, sourceHeight));
        const width = Math.max(1, Math.round(sourceWidth * analysisScale));
        const height = Math.max(1, Math.round(sourceHeight * analysisScale));
        const analysisCanvas = document.createElement('canvas');
        analysisCanvas.width = width;
        analysisCanvas.height = height;

        const context = analysisCanvas.getContext('2d', { willReadFrequently: true });
        if (!context) return url;

        context.drawImage(image, 0, 0, width, height);
        const pixels = context.getImageData(0, 0, width, height).data;
        const patchSize = Math.max(2, Math.min(8, Math.floor(Math.min(width, height) * 0.012)));

        const samplePatch = (startX, startY) => {
            const samples = [];

            for (let y = 0; y < patchSize; y += 1) {
                for (let x = 0; x < patchSize; x += 1) {
                    const px = Math.max(0, Math.min(width - 1, startX + x));
                    const py = Math.max(0, Math.min(height - 1, startY + y));
                    const offset = (py * width + px) * 4;
                    samples.push([
                        pixels[offset],
                        pixels[offset + 1],
                        pixels[offset + 2],
                        pixels[offset + 3],
                    ]);
                }
            }

            return [0, 1, 2, 3].map((channel) => {
                const values = samples.map((sample) => sample[channel]).sort((a, b) => a - b);
                return values[Math.floor(values.length / 2)] || 0;
            });
        };

        const cornerColours = [
            samplePatch(0, 0),
            samplePatch(Math.max(0, width - patchSize), 0),
            samplePatch(0, Math.max(0, height - patchSize)),
            samplePatch(Math.max(0, width - patchSize), Math.max(0, height - patchSize)),
        ];
        const mostlyLightCorners = cornerColours.filter(([r, g, b, a]) => (
            a < 16 || (r > 226 && g > 226 && b > 226)
        )).length >= 3;

        const isBackgroundPixel = (offset) => {
            const r = pixels[offset];
            const g = pixels[offset + 1];
            const b = pixels[offset + 2];
            const a = pixels[offset + 3];

            if (a <= 12) return true;
            if (mostlyLightCorners && r >= 238 && g >= 238 && b >= 238) return true;

            return cornerColours.some(([cr, cg, cb, ca]) => {
                if (Math.abs(a - ca) > 70) return false;
                const maxDifference = Math.max(Math.abs(r - cr), Math.abs(g - cg), Math.abs(b - cb));
                const averageDifference = (
                    Math.abs(r - cr) + Math.abs(g - cg) + Math.abs(b - cb)
                ) / 3;

                return maxDifference <= 26 && averageDifference <= 18;
            });
        };

        const rowHasContent = (y) => {
            let count = 0;
            const required = Math.max(3, Math.ceil(width * 0.006));

            for (let x = 0; x < width; x += 1) {
                if (!isBackgroundPixel((y * width + x) * 4)) {
                    count += 1;
                    if (count >= required) return true;
                }
            }

            return false;
        };

        const columnHasContent = (x, top, bottom) => {
            let count = 0;
            const required = Math.max(3, Math.ceil((bottom - top + 1) * 0.006));

            for (let y = top; y <= bottom; y += 1) {
                if (!isBackgroundPixel((y * width + x) * 4)) {
                    count += 1;
                    if (count >= required) return true;
                }
            }

            return false;
        };

        let top = 0;
        while (top < height - 1 && !rowHasContent(top)) top += 1;
        let bottom = height - 1;
        while (bottom > top && !rowHasContent(bottom)) bottom -= 1;
        let left = 0;
        while (left < width - 1 && !columnHasContent(left, top, bottom)) left += 1;
        let right = width - 1;
        while (right > left && !columnHasContent(right, top, bottom)) right -= 1;

        const padding = Math.max(1, Math.round(Math.min(width, height) * 0.004));
        top = Math.max(0, top - padding);
        left = Math.max(0, left - padding);
        bottom = Math.min(height - 1, bottom + padding);
        right = Math.min(width - 1, right + padding);

        const cropWidthRatio = (right - left + 1) / width;
        const cropHeightRatio = (bottom - top + 1) / height;
        const removedArea = 1 - (cropWidthRatio * cropHeightRatio);

        if (removedArea < 0.018 || cropWidthRatio < 0.12 || cropHeightRatio < 0.12) {
            previewCache.set(url, url);
            return url;
        }

        const sourceX = Math.max(0, Math.floor(left / analysisScale));
        const sourceY = Math.max(0, Math.floor(top / analysisScale));
        const cropWidth = Math.min(sourceWidth - sourceX, Math.ceil((right - left + 1) / analysisScale));
        const cropHeight = Math.min(sourceHeight - sourceY, Math.ceil((bottom - top + 1) / analysisScale));
        const outputLimit = 2800;
        const outputScale = Math.min(1, outputLimit / Math.max(cropWidth, cropHeight));
        const outputWidth = Math.max(1, Math.round(cropWidth * outputScale));
        const outputHeight = Math.max(1, Math.round(cropHeight * outputScale));
        const outputCanvas = document.createElement('canvas');
        outputCanvas.width = outputWidth;
        outputCanvas.height = outputHeight;

        const outputContext = outputCanvas.getContext('2d');
        if (!outputContext) return url;

        outputContext.drawImage(
            image,
            sourceX,
            sourceY,
            cropWidth,
            cropHeight,
            0,
            0,
            outputWidth,
            outputHeight
        );

        const objectUrl = await canvasToObjectUrl(outputCanvas);
        previewCache.set(url, objectUrl);
        return objectUrl;
    };

    window.nextPlayLockGalleryStage = function (stage) {
        if (!stage) return;

        const firstImage = stage.querySelector('.np-product-gallery-slide img');
        const applyRatio = function () {
            const width = Number(firstImage && firstImage.naturalWidth);
            const height = Number(firstImage && firstImage.naturalHeight);
            if (!width || !height) return;

            stage.style.setProperty('--np-gallery-stage-ratio', width + ' / ' + height);
            stage.dataset.stageLocked = 'true';
        };

        if (firstImage && firstImage.complete) {
            applyRatio();
        } else if (firstImage) {
            firstImage.addEventListener('load', applyRatio, { once: true });
        }
    };

    window.productImageViewer = function () {
        return {
            imageOpen: false,
            image: null,
            previewSrc: '',
            previewLoading: false,
            previewFailed: false,
            previewToken: 0,

            async open(image) {
                if (!image || !image.url) return;

                const token = ++this.previewToken;
                this.image = image;
                this.previewSrc = '';
                this.previewFailed = false;
                this.previewLoading = true;
                this.imageOpen = true;
                document.documentElement.classList.add('np-product-preview-open');

                try {
                    const preparedUrl = await trimOuterWhitespace(String(image.url));
                    if (token !== this.previewToken || !this.imageOpen) return;
                    this.previewSrc = preparedUrl || String(image.url);
                } catch (error) {
                    if (token !== this.previewToken || !this.imageOpen) return;
                    this.previewSrc = String(image.url);
                    this.previewFailed = true;
                } finally {
                    if (token === this.previewToken) this.previewLoading = false;
                }
            },

            close() {
                this.previewToken += 1;
                this.imageOpen = false;
                this.previewLoading = false;
                document.documentElement.classList.remove('np-product-preview-open');
            },
        };
    };

    window.addEventListener('beforeunload', () => {
        previewCache.forEach((cachedUrl, sourceUrl) => {
            if (cachedUrl !== sourceUrl && cachedUrl.indexOf('blob:') === 0) {
                URL.revokeObjectURL(cachedUrl);
            }
        });
    });
}());
