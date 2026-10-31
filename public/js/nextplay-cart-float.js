/* Position the existing cart preview above page/header clipping contexts.
 * No cart requests and no duplicate HTML; works with the existing summary. */
document.addEventListener('DOMContentLoaded', () => {
    const trigger = document.querySelector('.storefront-cart-hover');
    const preview = trigger?.querySelector('.storefront-cart-preview');
    if (!trigger || !preview) return;

    let shown = false;
    let closeTimer;
    const desktop = () => window.matchMedia('(min-width: 768px)').matches;
    const position = () => {
        if (!shown) return;
        const box = trigger.getBoundingClientRect();
        const width = Math.min(370, window.innerWidth - 24);
        const left = Math.max(12, Math.min(window.innerWidth - width - 12, box.right - width));
        const top = Math.max(8, box.bottom + 9);
        preview.style.left = `${left}px`;
        preview.style.top = `${top}px`;
        preview.style.right = 'auto';
        preview.style.width = `${width}px`;
        preview.style.maxHeight = `${Math.max(130, window.innerHeight - top - 12)}px`;
    };
    const open = () => {
        if (!desktop()) return;
        window.clearTimeout(closeTimer);
        shown = true;
        if (preview.parentElement !== document.body) document.body.append(preview);
        preview.classList.add('np-cart-preview-floating');
        position();
    };
    const close = () => {
        window.clearTimeout(closeTimer);
        shown = false;
        preview.classList.remove('np-cart-preview-floating');
        preview.style.cssText = '';
        if (preview.parentElement !== trigger) trigger.append(preview);
    };
    const scheduleClose = () => {
        window.clearTimeout(closeTimer);
        closeTimer = window.setTimeout(() => {
            const active = document.activeElement;
            if (trigger.matches(':hover') || preview.matches(':hover') || trigger.contains(active) || preview.contains(active)) return;
            close();
        }, 140);
    };
    trigger.addEventListener('mouseenter', open);
    trigger.addEventListener('mouseleave', scheduleClose);
    trigger.addEventListener('focusin', open);
    trigger.addEventListener('focusout', scheduleClose);
    preview.addEventListener('mouseenter', () => window.clearTimeout(closeTimer));
    preview.addEventListener('mouseleave', scheduleClose);
    preview.addEventListener('focusout', scheduleClose);
    window.addEventListener('scroll', position, {passive:true});
    window.addEventListener('resize', () => desktop() ? position() : close());
    document.addEventListener('pointerdown', e => {
        if (shown && !trigger.contains(e.target) && !preview.contains(e.target)) close();
    });
    document.addEventListener('keydown', e => {if (shown && e.key === 'Escape') close();});
});
