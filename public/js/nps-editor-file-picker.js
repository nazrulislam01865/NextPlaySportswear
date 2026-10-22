/* Consistent, accessible upload presentation for the six selected NPS editors.
 * The original file inputs, Alpine change handlers and form names stay intact.
 */
(function () {
    'use strict';

    function enhance(input) {
        // Inline/hidden upload triggers already have their own controls.
        if (input.dataset.npFilePicker === 'ready' || input.classList.contains('sr-only') || !input.closest('label')) {
            return;
        }

        input.dataset.npFilePicker = 'ready';
        input.classList.add('np-editor-file-picker__native');

        const display = document.createElement('span');
        display.className = 'np-editor-file-picker';
        display.setAttribute('aria-hidden', 'true');

        const choose = document.createElement('span');
        choose.className = 'np-editor-file-picker__choose';
        choose.textContent = 'Choose file';

        const name = document.createElement('span');
        name.className = 'np-editor-file-picker__name';
        name.textContent = input.disabled ? 'Upload unavailable' : 'No file chosen';

        display.append(choose, name);
        input.insertAdjacentElement('afterend', display);

        input.addEventListener('change', function () {
            const files = input.files;
            name.textContent = files && files.length
                ? (files.length === 1 ? files[0].name : files.length + ' files selected')
                : 'No file chosen';
            name.title = name.textContent;
        });
    }

    function start() {
        const editors = document.querySelectorAll('.np-editor');
        editors.forEach(function (editor) {
            editor.querySelectorAll('input[type="file"]').forEach(enhance);

            // Alpine creates/replaces hero slides dynamically; observe only this editor,
            // not the entire page, and skip changes that contain no file inputs.
            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType !== 1) return;
                        if (node.matches && node.matches('input[type="file"]')) {
                            enhance(node);
                        } else if (node.querySelectorAll) {
                            node.querySelectorAll('input[type="file"]').forEach(enhance);
                        }
                    });
                });
            });
            observer.observe(editor, { childList: true, subtree: true });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})();
