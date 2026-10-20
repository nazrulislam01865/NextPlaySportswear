/**
 * NextPlay viewer timezone renderer. UI display only: storage, schedules,
 * payment timestamps and date-only fields are never mutated.
 * Uses the IANA timezone detected by this browser, including DST transitions.
 */
(() => {
    'use strict';

    const selector = 'time[data-np-local-time]';
    const inputSelector = 'input[type="datetime-local"][data-np-utc-input]';
    const locale = 'en-US';
    const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
    const fullFormatter = new Intl.DateTimeFormat(locale, {
        timeZone,
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        weekday: 'long',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hourCycle: 'h23',
    });
    const monthNumericFormatter = new Intl.DateTimeFormat(locale, { timeZone, month: 'numeric' });
    const zoneNameFormatter = new Intl.DateTimeFormat(locale, { timeZone, timeZoneName: 'short' });
    const monthShortFormatter = new Intl.DateTimeFormat(locale, { timeZone, month: 'short' });
    const weekdayShortFormatter = new Intl.DateTimeFormat(locale, { timeZone, weekday: 'short' });

    const pad2 = (value) => String(value).padStart(2, '0');

    function renderPhpStyle(date, format) {
        const parts = Object.fromEntries(fullFormatter.formatToParts(date).map(({ type, value }) => [type, value]));
        const year = Number(parts.year);
        const month = Number(monthNumericFormatter.format(date));
        const day = Number(parts.day);
        const hour = Number(parts.hour);
        const minute = Number(parts.minute);
        const second = Number(parts.second);
        const hour12 = hour % 12 || 12;
        const tokens = {
            Y: String(year), y: pad2(year % 100),
            m: pad2(month), n: String(month),
            F: parts.month, M: monthShortFormatter.format(date),
            d: pad2(day), j: String(day),
            l: parts.weekday, D: weekdayShortFormatter.format(date),
            H: pad2(hour), G: String(hour),
            h: pad2(hour12), g: String(hour12),
            i: pad2(minute), s: pad2(second),
            A: hour < 12 ? 'AM' : 'PM', a: hour < 12 ? 'am' : 'pm',
        };
        // PHP formatting: a backslash makes the next character literal.
        let result = '';
        for (let index = 0; index < format.length; index++) {
            const char = format[index];
            if (char === '\\' && index + 1 < format.length) {
                result += format[++index];
            } else {
                result += Object.prototype.hasOwnProperty.call(tokens, char) ? tokens[char] : char;
            }
        }
        return result;
    }

    function convert(element) {
        if (element.dataset.npLocalized === 'true') return;
        const utc = element.getAttribute('datetime');
        const format = element.dataset.npPhpFormat;
        if (!utc || !format) return;
        const date = new Date(utc);
        if (Number.isNaN(date.getTime())) return;

        try {
            element.textContent = renderPhpStyle(date, format);
            const zoneAbbreviation = zoneNameFormatter.formatToParts(date).find((part) => part.type === 'timeZoneName')?.value;
            element.title = `Time shown in your timezone (${timeZone}${zoneAbbreviation ? `, ${zoneAbbreviation}` : ''})`;
            element.dataset.npLocalized = 'true';
        } catch (_) {
            // Keep server-rendered UTC text rather than risk an invalid date.
        }
    }

    const localDateTime = (date) => `${date.getFullYear()}-${pad2(date.getMonth() + 1)}-${pad2(date.getDate())}T${pad2(date.getHours())}:${pad2(date.getMinutes())}`;

    function prepareInput(input) {
        if (input.dataset.npUtcPrepared === 'true') return;
        input.dataset.npUtcPrepared = 'true';
        if (input.value) {
            // Values from Laravel are UTC wall-clock timestamps (no suffix).
            const instant = new Date(`${input.value.slice(0, 16)}:00Z`);
            if (!Number.isNaN(instant.getTime())) {
                input.dataset.npOriginalUtc = input.value.slice(0, 16);
                input.value = localDateTime(instant);
                input.dataset.npOriginalLocal = input.value;
            }
        }
        input.title = `Enter time in your timezone (${timeZone})`;
        input.addEventListener('input', () => input.setCustomValidity(''));
        input.addEventListener('change', () => {
            if (!input.value) return;
            const parsed = new Date(input.value);
            if (Number.isNaN(parsed.getTime()) || localDateTime(parsed) !== input.value.slice(0, 16)) {
                input.setCustomValidity('Choose a valid local date and time (this time may not exist during a daylight saving change).');
            } else {
                input.setCustomValidity('');
            }
        });
    }

    function update(root) {
        if (root.nodeType === 1) {
            if (root.matches(selector)) convert(root);
            if (root.matches(inputSelector)) prepareInput(root);
        }
        if (root.querySelectorAll) {
            root.querySelectorAll(selector).forEach(convert);
            root.querySelectorAll(inputSelector).forEach(prepareInput);
        }
    }

    function start() {
        update(document);
        // The formdata event preserves the visible local value but sends the
        // equivalent UTC wall-clock to existing Laravel validation/controllers.
        // No request middleware, DB timezone or scheduling rules are changed.
        document.addEventListener('formdata', (event) => {
            if (!event.target?.querySelectorAll) return;
            event.target.querySelectorAll(inputSelector).forEach((input) => {
                if (!input.name || !input.value) return;
                // Preserve the original instant on DST fallback's repeated
                // 1 AM hour when a field is submitted without edits.
                if (input.dataset.npOriginalLocal === input.value && input.dataset.npOriginalUtc) {
                    event.formData.set(input.name, input.dataset.npOriginalUtc);
                    return;
                }
                const local = new Date(input.value);
                if (Number.isNaN(local.getTime()) || localDateTime(local) !== input.value.slice(0, 16)) return;
                event.formData.set(input.name, `${local.getUTCFullYear()}-${pad2(local.getUTCMonth() + 1)}-${pad2(local.getUTCDate())}T${pad2(local.getUTCHours())}:${pad2(local.getUTCMinutes())}`);
            });
        }, true);
        // Admin notification panels and account fragments can arrive by AJAX.
        if (typeof MutationObserver !== 'undefined' && document.body) {
            const observer = new MutationObserver((records) => {
                for (const record of records) {
                    record.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) update(node);
                    });
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})();
