/* Enhance native checkout selects visually without changing submitted values.
   Custom popovers match the NextPlay shipping-dropdown reference. */
document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('.np-checkout--prototype');
    if (!root) return;
    let openDropdown = null;

    root.querySelectorAll('select[data-nps-dropdown]').forEach((select, index) => {
        if (select.dataset.npsEnhanced) return;
        select.dataset.npsEnhanced = 'true';
        const shell = document.createElement('div');
        shell.className = 'np-select-shell';
        // The phone-code select is a fixed-width segment of the joined phone field.
        // Other selectors (country, address) remain full-width.
        if (select.dataset.npsDropdown === 'phone') shell.classList.add('np-select-shell--phone');
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'np-select-trigger';
        button.id = `np-select-trigger-${index}`;
        button.setAttribute('aria-haspopup','listbox');
        button.setAttribute('aria-expanded','false');
        button.setAttribute('aria-label', select.getAttribute('aria-label') || select.closest('.nps-field')?.querySelector('label')?.textContent?.trim() || 'Select option');
        const title = document.createElement('span');
        title.className = 'np-select-trigger-title';
        const chevron = document.createElement('span');
        chevron.className = 'np-select-arrow';
        chevron.setAttribute('aria-hidden','true');
        chevron.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>';
        button.append(title, chevron);
        const menu = document.createElement('div');
        menu.className = 'np-select-list';
        menu.id = `np-select-list-${index}`;
        menu.setAttribute('role','listbox');
        menu.setAttribute('aria-label',button.getAttribute('aria-label'));
        button.setAttribute('aria-controls',menu.id);
        menu.hidden = true;
        const options = [];
        for (const [i, option] of Array.from(select.options).entries()) {
            const choice = document.createElement('button');
            choice.type = 'button';
            choice.className = 'np-select-option';
            choice.setAttribute('role','option');
            choice.id = `${menu.id}-option-${i}`;
            choice.dataset.value = option.value;
            choice.textContent = option.textContent.trim();
            choice.disabled = option.disabled;
            choice.setAttribute('aria-selected', 'false');
            choice.addEventListener('click', () => {
                select.value = option.value;
                select.dispatchEvent(new Event('change', {bubbles:true}));
                close();
                button.focus();
            });
            menu.append(choice);
            options.push(choice);
        }
        shell.append(button, menu);
        select.insertAdjacentElement('afterend', shell);
        select.classList.add('np-select-native');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden','true');

        const sync = () => {
            const selected = select.selectedOptions[0];
            title.textContent = selected?.textContent.trim() || 'Select';
            const activeIndex = Math.max(0, select.selectedIndex);
            options.forEach((option, i) => {
                const active = i === activeIndex;
                option.classList.toggle('is-selected',active);
                option.setAttribute('aria-selected',String(active));
            });
        };
        const close = () => {
            menu.hidden = true;
            shell.classList.remove('is-open');
            button.setAttribute('aria-expanded','false');
            if (openDropdown === shell) openDropdown = null;
        };
        const open = () => {
            if (openDropdown && openDropdown !== shell) openDropdown.querySelector('.np-select-trigger')?.click();
            sync();
            menu.hidden = false;
            shell.classList.add('is-open');
            button.setAttribute('aria-expanded','true');
            openDropdown = shell;
            options[select.selectedIndex]?.scrollIntoView({block:'nearest'});
        };
        button.addEventListener('click', () => menu.hidden ? open() : close());
        button.addEventListener('keydown', e => {
            if (e.key === 'Escape') {close();return;}
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                if (menu.hidden) open();
                const optionsEnabled = options.filter(o => !o.disabled);
                const current = optionsEnabled.indexOf(document.activeElement);
                const selected = Math.max(0, optionsEnabled.findIndex(o => o.classList.contains('is-selected')));
                const next = e.key === 'ArrowUp' ? (current < 0 ? selected : Math.max(0,current-1)) : (current < 0 ? selected : Math.min(optionsEnabled.length-1,current+1));
                optionsEnabled[next]?.focus();
            }
        });
        menu.addEventListener('keydown', e => {
            const enabled = options.filter(o => !o.disabled);
            const i = enabled.indexOf(document.activeElement);
            if (e.key === 'Escape') {e.preventDefault();close();button.focus();}
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();enabled[Math.min(enabled.length-1,Math.max(0,i+(e.key==='ArrowDown'?1:-1)))]?.focus();
            }
            if (e.key === 'Home' || e.key === 'End') {e.preventDefault();enabled[e.key==='Home'?0:enabled.length-1]?.focus();}
        });
        select.addEventListener('change',sync);
        select.addEventListener('np-select-sync',sync);
        select.addEventListener('invalid', () => {button.focus();},true);
        sync();
        document.addEventListener('pointerdown',e=>{if (!shell.contains(e.target)) close();});
        document.addEventListener('keydown',e=>{if(e.key==='Escape')close();});
    });
});
