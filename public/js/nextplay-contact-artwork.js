/* Contact Us attachment picker: Bulk Quote-style preview, drag/drop and removal. */
(() => {
    'use strict';
    const input = document.getElementById('contact-attachment');
    const dropzone = document.getElementById('contact-artwork-dropzone');
    const preview = document.getElementById('contact-artwork-preview');
    const image = document.getElementById('contact-artwork-image');
    const name = document.getElementById('contact-artwork-name');
    const size = document.getElementById('contact-artwork-size');
    const remove = document.getElementById('contact-artwork-remove');
    const error = document.getElementById('contact-artwork-client-error');
    const serverError = document.getElementById('contact-attachment-error');
    if (!input || !dropzone || !preview || !image || !name || !size || !remove || !error) return;
    let objectUrl = null;
    const types = ['application/pdf', 'image/jpeg', 'image/png'];
    const maxBytes = 10 * 1024 * 1024;
    const clearObjectUrl = () => { if (objectUrl) URL.revokeObjectURL(objectUrl); objectUrl = null; };
    const setError = (message) => {
        error.textContent = message;
        error.hidden = !message;
        if (message) input.setAttribute('aria-invalid', 'true');
        else input.setAttribute('aria-invalid', 'false');
    };
    const valid = (file) => {
        if (!file) return true;
        if (!types.includes(file.type) || !/\.(pdf|jpe?g|png)$/i.test(file.name)) {
            setError('Upload a PDF, JPG or PNG attachment.'); return false;
        }
        if (file.size > maxBytes) { setError('The attachment must not exceed 10 MB.'); return false; }
        return true;
    };
    const reset = () => {
        input.value = '';
        clearObjectUrl();
        image.hidden = true;
        image.removeAttribute('src');
        name.textContent = '';
        size.textContent = '';
        preview.hidden = true;
    };
    const show = () => {
        clearObjectUrl();
        const file = input.files && input.files[0];
        if (!file) { reset(); return; }
        if (!valid(file)) { reset(); return; }
        setError('');
        if (serverError) serverError.hidden = true;
        name.textContent = file.name;
        size.textContent = `${Math.ceil(file.size / 1024)} KB`;
        if (file.type.startsWith('image/')) {
            objectUrl = URL.createObjectURL(file);
            image.src = objectUrl;
            image.hidden = false;
        } else {
            image.hidden = true;
            image.removeAttribute('src');
        }
        preview.hidden = false;
    };
    input.addEventListener('change', show);
    remove.addEventListener('click', () => { reset(); setError(''); input.focus(); });
    dropzone.addEventListener('dragover', (event) => { event.preventDefault(); dropzone.classList.add('is-dragging'); });
    dropzone.addEventListener('dragleave', (event) => {
        if (!dropzone.contains(event.relatedTarget)) dropzone.classList.remove('is-dragging');
    });
    dropzone.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.classList.remove('is-dragging');
        const file = event.dataTransfer?.files?.[0];
        if (!file) return;
        if (!valid(file)) { reset(); return; }
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
        show();
    });
    input.form?.addEventListener('submit', (event) => {
        if (!valid(input.files?.[0])) { event.preventDefault(); input.focus(); }
    });
    window.addEventListener('pagehide', clearObjectUrl, { once: true });
})();
