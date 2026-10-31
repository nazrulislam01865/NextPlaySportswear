/* Shared storefront sidebar positioning: no nested scrollbars or header overlap. */
(() => {
  'use strict';
  const mount = () => {
    const panels = [...document.querySelectorAll('.np-checkout--prototype .np-checkout__summary, .npq-page .npq-added, .np-cart-page .np-cart-summary')];
    if (!panels.length) return;
    const header = document.querySelector('.storefront-site-header.np-site-header');
    let frame = 0;
    const update = () => {
      frame = 0;
      // Header is sticky, so its actual visible height is the safe offset.
      const offset = Math.ceil(header?.getBoundingClientRect().height || 0) + 14;
      document.documentElement.style.setProperty('--np-summary-sticky-top', `${offset}px`);
      const available = Math.max(0, window.innerHeight - offset - 18);
      panels.forEach((panel) => {
        // Too-tall sidebars must use document scrolling rather than be clipped.
        const disable = panel.getBoundingClientRect().height > available || window.innerWidth <= 825;
        panel.classList.toggle('np-summary-no-sticky', disable);
      });
    };
    const schedule = () => {
      if (frame) return;
      frame = requestAnimationFrame(update);
    };
    window.addEventListener('resize', schedule, { passive: true });
    if ('ResizeObserver' in window) {
      const observer = new ResizeObserver(schedule);
      if (header) observer.observe(header);
      panels.forEach((panel) => observer.observe(panel));
    }
    schedule();
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount, {once:true});
  else mount();
})();
