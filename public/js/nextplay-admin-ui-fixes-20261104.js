/* NextPlay admin UX: transient messages and overflow-safe personalization actions. */
/**
 * Flash notices are transient; form validation blocks are deliberately excluded.
 * Works on initial page load and on the optional admin soft-navigation lifecycle.
 */
const initializeTransientAdminFlash = () => {
    document.querySelectorAll('[data-admin-flash]').forEach((notice) => {
        if (notice.dataset.adminFlashReady === 'true') return;
        notice.dataset.adminFlashReady = 'true';
        const isError = notice.className.includes('red-') || notice.className.includes('rose-');
        if (!notice.hasAttribute('role')) notice.setAttribute('role', isError ? 'alert' : 'status');

        const dismiss = () => {
            if (!notice.isConnected || notice.classList.contains('admin-flash-dismissing')) return;
            notice.classList.add('admin-flash-dismissing');
            window.setTimeout(() => notice.remove(), 240);
        };
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'admin-flash-close';
        close.setAttribute('aria-label', 'Dismiss notification');
        close.textContent = '×';
        close.addEventListener('click', dismiss);
        notice.appendChild(close);
        window.setTimeout(dismiss, isError ? 7000 : 4500);
    });
};

/**
 * The table has horizontal overflow on smaller screens. Render the actions panel
 * at the document top layer and measure against the viewport (flip when near the
 * bottom). This prevents clipping on the last row and during horizontal scroll.
 */
const initializePersonalizationActionMenus = () => {
    if (document.documentElement.dataset.ptActionMenusReady === 'true') return;
    document.documentElement.dataset.ptActionMenusReady = 'true';
    let current = null;

    const close = (restoreFocus = false) => {
        if (!current) return;
        const { owner, trigger, panel } = current;
        panel.hidden = true;
        panel.classList.remove('pt-floating-menu');
        panel.style.left = '';
        panel.style.top = '';
        panel.style.maxHeight = '';
        owner.appendChild(panel);
        trigger.setAttribute('aria-expanded', 'false');
        current = null;
        if (restoreFocus && trigger.isConnected) trigger.focus();
    };

    const reposition = () => {
        if (!current) return;
        const { panel, trigger } = current;
        if (!trigger.isConnected) { close(); return; }
        const rect = trigger.getBoundingClientRect();
        if (rect.bottom < 0 || rect.top > window.innerHeight || rect.right < 0 || rect.left > window.innerWidth) {
            close();
            return;
        }
        const margin = 10;
        const gap = 5;
        const width = panel.offsetWidth;
        const height = panel.offsetHeight;
        const spaceBelow = window.innerHeight - rect.bottom - margin;
        const spaceAbove = rect.top - margin;
        const openAbove = spaceBelow < Math.min(height, 200) && spaceAbove > spaceBelow;
        panel.style.maxHeight = `${Math.max(80, (openAbove ? spaceAbove : spaceBelow) - gap)}px`;
        const effectiveHeight = Math.min(height, panel.offsetHeight);
        const top = openAbove ? rect.top - gap - effectiveHeight : rect.bottom + gap;
        panel.style.top = `${Math.max(margin, Math.min(top, window.innerHeight - margin - effectiveHeight))}px`;
        panel.style.left = `${Math.max(margin, Math.min(rect.right - width, window.innerWidth - width - margin))}px`;
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-pt-action-trigger]');
        if (trigger) {
            event.preventDefault();
            if (current?.trigger === trigger) { close(); return; }
            close();
            const owner = trigger.closest('[data-pt-action-menu]');
            const panel = owner?.querySelector('.pt-menu-panel');
            if (!panel) return;
            current = { owner, trigger, panel };
            panel.hidden = false;
            panel.classList.add('pt-floating-menu');
            document.body.appendChild(panel);
            trigger.setAttribute('aria-expanded', 'true');
            reposition();
            return;
        }
        if (current && !current.panel.contains(event.target)) close();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && current) { event.preventDefault(); close(true); }
    });
    // Capture scroll events from the table container, document, and sidebars.
    document.addEventListener('scroll', reposition, true);
    window.addEventListener('resize', reposition);
    document.addEventListener('nextplay:admin-navigated', () => close());
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initializeTransientAdminFlash();
        initializePersonalizationActionMenus();
    }, { once:true });
} else {
    initializeTransientAdminFlash();
    initializePersonalizationActionMenus();
}
document.addEventListener('nextplay:admin-navigated', initializeTransientAdminFlash);
