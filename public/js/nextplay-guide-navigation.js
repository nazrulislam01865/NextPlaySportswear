/* One navigation controller for NextPlay's admin-managed guides.
   Uses viewport coordinates for every section; avoids repeated, competing ScrollSpy handlers. */
document.addEventListener('DOMContentLoaded', () => {
  const page = document.querySelector('.np-guide-page');
  if (!page) return;
  const links = Array.from(page.querySelectorAll('.np-sidebar-link[href^="#"], .np-size-nav-link[href^="#"]'));
  const entries = links.map(link => ({link, section: document.getElementById(link.hash.slice(1))}))
    .filter(entry => entry.section);
  if (!entries.length) return;
  let scheduled = false;
  const activate = selected => {
    for (const entry of entries) {
      const active = entry === selected;
      entry.link.classList.toggle('is-active', active);
      if (active) entry.link.setAttribute('aria-current', 'location');
      else entry.link.removeAttribute('aria-current');
    }
  };
  const update = () => {
    scheduled = false;
    const cutoff = Math.min(180, Math.max(100, window.innerHeight * .25));
    let active = entries[0];
    for (const entry of entries) {
      if (entry.section.getBoundingClientRect().top <= cutoff) active = entry;
    }
    // The final section may never reach the top cutoff on short pages.
    if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 3) {
      active = entries[entries.length - 1];
    }
    activate(active);
  };
  const onScroll = () => {
    if (scheduled) return;
    scheduled = true;
    window.requestAnimationFrame(update);
  };
  for (const entry of entries) {
    entry.link.addEventListener('click', () => activate(entry));
  }
  window.addEventListener('scroll', onScroll, {passive: true});
  window.addEventListener('resize', onScroll, {passive: true});
  window.addEventListener('hashchange', onScroll);
  update();
});
