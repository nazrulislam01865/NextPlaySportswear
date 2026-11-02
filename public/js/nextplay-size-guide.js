document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('.np-size-page');
    if (!page) return;
    const entries = [...page.querySelectorAll('.np-size-nav-link[href^="#"]')]
        .map(link => ({link, section: document.getElementById(link.getAttribute('href').slice(1))}))
        .filter(entry => entry.section);
    if (!entries.length) return;
    let scheduled = false;
    const update = () => {
        scheduled = false;
        const cutoff = Math.min(180, Math.max(100, window.innerHeight * .25));
        let active = entries[0];
        for (const entry of entries) {
            if (entry.section.getBoundingClientRect().top <= cutoff) active = entry;
        }
        if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 3) active = entries[entries.length - 1];
        for (const entry of entries) {
            entry.link.classList.toggle('is-active', entry === active);
            if (entry === active) entry.link.setAttribute('aria-current', 'location');
            else entry.link.removeAttribute('aria-current');
        }
    };
    const onScroll = () => {
        if (scheduled) return;
        scheduled = true;
        requestAnimationFrame(update);
    };
    window.addEventListener('scroll', onScroll, {passive: true});
    window.addEventListener('resize', onScroll, {passive: true});
    entries.forEach(({link}) => link.addEventListener('click', () => {
        entries.forEach(entry => entry.link.classList.remove('is-active'));
        link.classList.add('is-active');
    }));
    update();
});
