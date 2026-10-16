// Progressive enhancement, scoped to the existing Storefront Content editors.
// Native multipart inputs are retained; only their selection presentation changes.
export function initializeAdminContentEditors() {
    document.querySelectorAll('.np-storefront-editor input[type="file"][data-content-file]').forEach((input, index) => {
        if (input.dataset.enhanced === 'true') return;
        input.dataset.enhanced = 'true';

        if (!input.id) input.id = `np-content-file-${index}`;
        const wrap = document.createElement('div');
        wrap.className = 'np-content-file';
        const button = document.createElement('label');
        button.className = 'np-content-file__choose';
        button.htmlFor = input.id;
        button.textContent = 'Choose file';
        const name = document.createElement('span');
        name.className = 'np-content-file__name';
        name.textContent = input.disabled ? 'Uploads unavailable' : 'No file selected';
        name.setAttribute('aria-live', 'polite');
        const clear = document.createElement('button');
        clear.type = 'button';
        clear.className = 'np-content-file__clear';
        clear.textContent = 'Clear';
        clear.hidden = true;
        clear.disabled = input.disabled;
        clear.setAttribute('aria-label', 'Clear selected file');
        const preview = document.createElement('img');
        preview.className = 'np-content-file__preview';
        preview.alt = 'Selected image preview';
        preview.hidden = true;
        let objectUrl = null;

        input.parentNode.insertBefore(wrap, input);
        wrap.append(input, button, name, clear, preview);
        input.classList.add('np-content-file__input');
        if (input.disabled) button.classList.add('np-content-file__choose--disabled');

        const update = () => {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = null;
            const file = input.files?.[0];
            name.textContent = file ? `${file.name} (${(file.size / 1024).toFixed(0)} KB)` : 'No file selected';
            clear.hidden = !file;
            preview.hidden = true;
            preview.removeAttribute('src');
            if (file && file.type.startsWith('image/')) {
                objectUrl = URL.createObjectURL(file);
                preview.src = objectUrl;
                preview.hidden = false;
            }
        };
        input.addEventListener('change', update);
        clear.addEventListener('click', () => {
            input.value = '';
            update();
        });
    });
}
