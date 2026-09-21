const DEFAULT_CHUNK_SIZE = 512 * 1024;
const MAX_FILE_SIZE = 10 * 1024 * 1024;
const ALLOWED_TYPES = new Set(['image/jpeg', 'image/png', 'image/webp', 'image/avif']);

export function tokenFieldName(fileFieldName = '') {
    const name = String(fileFieldName || '');
    if (name === 'image_file') return 'image_upload_token';
    if (name.endsWith('[image_file]')) return `${name.slice(0, -'[image_file]'.length)}[image_upload_token]`;
    return null;
}

export function splitIntoChunkRanges(size, chunkSize = DEFAULT_CHUNK_SIZE) {
    const total = Math.max(0, Number(size) || 0);
    const chunk = Math.max(1, Number(chunkSize) || DEFAULT_CHUNK_SIZE);
    const ranges = [];
    for (let start = 0; start < total; start += chunk) {
        ranges.push([start, Math.min(start + chunk, total)]);
    }
    return ranges;
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function ensureHiddenToken(form, input) {
    const name = tokenFieldName(input.name);
    if (!name) return null;

    let hidden = Array.from(form.elements).find((element) => element?.name === name);
    if (!(hidden instanceof HTMLInputElement)) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = name;
        input.insertAdjacentElement('afterend', hidden);
    }
    return hidden;
}

function ensureStatus(input) {
    let status = input.parentElement?.querySelector('[data-homepage-upload-status]');
    if (!status) {
        status = document.createElement('span');
        status.dataset.homepageUploadStatus = '1';
        status.className = 'mt-1.5 block text-xs font-semibold text-slate-500';
        input.insertAdjacentElement('afterend', status);
    }
    return status;
}

function setStatus(status, text, state = 'neutral') {
    status.textContent = text;
    status.classList.remove('text-slate-500', 'text-emerald-700', 'text-red-700');
    status.classList.add(state === 'success' ? 'text-emerald-700' : state === 'error' ? 'text-red-700' : 'text-slate-500');
}

async function parseJson(response) {
    try {
        return await response.json();
    } catch {
        return {};
    }
}

async function stageFile(form, input, file) {
    if (!ALLOWED_TYPES.has(file.type)) {
        throw new Error('Choose a JPG, PNG, WebP, or AVIF image.');
    }
    if (file.size <= 0) {
        throw new Error('The selected image is empty.');
    }
    if (file.size > MAX_FILE_SIZE) {
        throw new Error('The image must be no larger than 10 MB.');
    }

    const baseUrl = String(form.dataset.homepageUploadBaseUrl || '').replace(/\/$/, '');
    if (!baseUrl) {
        throw new Error('Homepage uploader is not configured. Refresh the page and try again.');
    }

    const uploadId = globalThis.crypto?.randomUUID?.() || `${Date.now()}-${Math.random().toString(36).slice(2)}`;
    const ranges = splitIntoChunkRanges(file.size);
    const headers = {
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
    };

    for (let index = 0; index < ranges.length; index += 1) {
        const [start, end] = ranges[index];
        const response = await fetch(`${baseUrl}/${encodeURIComponent(uploadId)}/chunks/${index}`, {
            method: 'PUT',
            headers: {
                ...headers,
                'Content-Type': 'application/octet-stream',
            },
            body: file.slice(start, end),
            credentials: 'same-origin',
        });
        if (!response.ok) {
            const payload = await parseJson(response);
            throw new Error(payload.message || `Image upload failed while sending chunk ${index + 1}.`);
        }
    }

    const response = await fetch(`${baseUrl}/${encodeURIComponent(uploadId)}/finalize`, {
        method: 'POST',
        headers: {
            ...headers,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            original_name: file.name,
            size: file.size,
            chunks: ranges.length,
            mime_type: file.type,
        }),
    });

    const payload = await parseJson(response);
    if (!response.ok || !payload.token) {
        const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
        throw new Error(firstError || payload.message || 'The image could not be prepared for saving.');
    }

    return payload;
}

export function initHomepageStagedUploads() {
    document.addEventListener('change', async (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || input.type !== 'file') return;

        const form = input.closest('form[data-homepage-upload-form]');
        if (!(form instanceof HTMLFormElement)) return;
        if (!tokenFieldName(input.name)) return;

        const file = input.files?.[0];
        if (!file) return;

        const hidden = ensureHiddenToken(form, input);
        const status = ensureStatus(input);
        if (!hidden) return;

        hidden.value = '';
        hidden.dispatchEvent(new Event('input', { bubbles: true }));
        input.dataset.homepageUploadState = 'pending';
        form.dataset.homepageUploadPending = String((Number(form.dataset.homepageUploadPending || 0) || 0) + 1);
        const sequence = (Number(input.dataset.homepageUploadSequence || 0) || 0) + 1;
        input.dataset.homepageUploadSequence = String(sequence);
        setStatus(status, 'Preparing image upload…');

        try {
            const payload = await stageFile(form, input, file);
            if (Number(input.dataset.homepageUploadSequence || 0) !== sequence) return;
            hidden.value = payload.token;
            hidden.dispatchEvent(new Event('input', { bubbles: true }));
            input.dataset.homepageUploadState = 'ready';
            input.value = '';
            const dimensions = payload.width && payload.height ? ` (${payload.width}×${payload.height})` : '';
            setStatus(status, `Image ready to save${dimensions}.`, 'success');
        } catch (error) {
            if (Number(input.dataset.homepageUploadSequence || 0) !== sequence) return;
            hidden.value = '';
            hidden.dispatchEvent(new Event('input', { bubbles: true }));
            input.dataset.homepageUploadState = 'error';
            setStatus(status, error instanceof Error ? error.message : 'Image upload failed. Please choose the file again.', 'error');
        } finally {
            const pending = Math.max(0, (Number(form.dataset.homepageUploadPending || 0) || 1) - 1);
            form.dataset.homepageUploadPending = String(pending);
        }
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-homepage-upload-form]')) return;

        const pending = Number(form.dataset.homepageUploadPending || 0) || 0;
        const brokenInput = Array.from(form.querySelectorAll('input[type="file"]'))
            .find((input) => input.dataset.homepageUploadState === 'error' && input.files?.length);

        if (pending > 0 || brokenInput) {
            event.preventDefault();
            const message = pending > 0
                ? 'Please wait for the image upload to finish before saving.'
                : 'One image could not be uploaded. Choose that image again before saving.';
            window.alert(message);
            brokenInput?.focus();
        }
    }, true);
}
