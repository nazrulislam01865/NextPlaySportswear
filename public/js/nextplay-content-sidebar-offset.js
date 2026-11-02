/* Offset all information-page sidebars below the real sticky header height.
   The header has responsive rows; a hard-coded top value hides "On this page". */
(() => {
  'use strict';
  const header = document.querySelector('.np-site-header');
  if (!header) return;

  let frame = 0;
  const sync = () => {
    frame = 0;
    const height = Math.ceil(header.getBoundingClientRect().height);
    if (height > 0) {
      document.documentElement.style.setProperty('--np-content-sticky-top', `${height + 16}px`);
    }
  };
  const requestSync = () => {
    if (!frame) frame = window.requestAnimationFrame(sync);
  };

  requestSync();
  window.addEventListener('load', requestSync, { once: true });
  window.addEventListener('resize', requestSync, { passive: true });
  if (typeof ResizeObserver !== 'undefined') {
    new ResizeObserver(requestSync).observe(header);
  }
})();
