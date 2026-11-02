/* Scoped shipping guide scroll tracking. */
document.addEventListener('DOMContentLoaded', () => {
  const root = document.querySelector('.np-shipping-page');
  if (!root) return;
  const links = Array.from(root.querySelectorAll('.np-sidebar-link[href^="#"]'));
  const targets = links.map(link => ({link, section: document.getElementById(link.hash.slice(1))})).filter(item => item.section);
  if (!targets.length) return;
  function updateActive() {
    const threshold = 165;
    let active = targets[0];
    for (const item of targets) {
      if (item.section.getBoundingClientRect().top <= threshold) active = item;
    }
    const end = document.documentElement.scrollHeight - (window.scrollY + window.innerHeight);
    if (end <= 3) active = targets[targets.length - 1];
    for (const item of targets) {
      const selected = item === active;
      item.link.classList.toggle('is-active', selected);
      if (selected) item.link.setAttribute('aria-current', 'location');
      else item.link.removeAttribute('aria-current');
    }
  }
  window.addEventListener('scroll', updateActive, {passive:true});
  window.addEventListener('resize', updateActive, {passive:true});
  updateActive();
});
