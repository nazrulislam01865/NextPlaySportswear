/* Payment Information uses the same section tracking as Returns & Refunds.
   At document end the final heading cannot reach the normal scroll cutoff;
   deliberately activate the final link then. No observer threshold race. */
(() => {
    'use strict';
    const navigation = document.getElementById('np-payment-sidebar-nav');
    if (!navigation) return;

    const entries = [...navigation.querySelectorAll('a[href^="#payment-section-"]')]
        .map((link) => ({ link, section: document.getElementById(link.getAttribute('href').slice(1)) }))
        .filter(({ section }) => section !== null);
    if (!entries.length) return;

    function activate(entry) {
        entries.forEach(({ link }) => {
            const selected = link === entry.link;
            link.classList.toggle('is-active', selected);
            if (selected) link.setAttribute('aria-current', 'location');
            else link.removeAttribute('aria-current');
        });
    }

    function updateActive() {
        queued = false;
        const headerOffset = parseFloat(getComputedStyle(document.documentElement)
            .getPropertyValue('--np-content-sticky-top')) || 120;
        const cutoff = Math.min(window.innerHeight * 0.38, headerOffset + 56);
        let current = entries[0];

        entries.forEach((entry) => {
            if (entry.section.getBoundingClientRect().top <= cutoff) current = entry;
        });

        // A short last section may never reach the cutoff before the page ends.
        const doc = document.documentElement;
        const bottom = window.scrollY + window.innerHeight >= doc.scrollHeight - 4;
        const last = entries[entries.length - 1];
        if (bottom || (last.section.getBoundingClientRect().top < window.innerHeight * 0.65
            && last.section.getBoundingClientRect().bottom <= window.innerHeight * 0.85)) {
            current = last;
        }
        activate(current);
    }

    let queued = false;
    function scheduleUpdate() {
        if (queued) return;
        queued = true;
        window.requestAnimationFrame(updateActive);
    }

    entries.forEach((entry) => {
        entry.link.addEventListener('click', () => activate(entry));
    });
    window.addEventListener('scroll', scheduleUpdate, { passive: true });
    window.addEventListener('resize', scheduleUpdate, { passive: true });
    window.addEventListener('load', scheduleUpdate, { once: true });
    scheduleUpdate();
})();
