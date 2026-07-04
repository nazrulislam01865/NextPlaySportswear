(() => {
    'use strict';

    const config = window.NEXTPLAY_ADMIN_NOTIFICATIONS || {};
    const widget = document.getElementById('adminNotificationWidget');
    if (!widget || !config.feedUrl) return;

    const bell = document.getElementById('adminNotificationBell');
    const panel = document.getElementById('adminNotificationPanel');
    const list = document.getElementById('adminNotificationList');
    const count = document.getElementById('adminNotificationCount');
    const status = document.getElementById('adminNotificationStatus');
    const markAll = document.getElementById('adminMarkAllRead');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let notifications = [];
    let unreadCount = 0;

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    function updateCount(value) {
        unreadCount = Math.max(0, Number(value) || 0);
        if (count) {
            count.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
            count.classList.toggle('hidden', unreadCount === 0);
        }
        if (status) {
            status.textContent = unreadCount ? `${unreadCount} unread` : 'All caught up';
        }
    }

    function formatTime(value) {
        if (!value) return 'Just now';
        const parsed = new Date(value);
        return Number.isNaN(parsed.getTime()) ? 'Just now' : parsed.toLocaleString();
    }

    function itemMarkup(notification) {
        const data = notification.data || {};
        const unread = !notification.read_at;
        const id = escapeHtml(notification.id || '');
        const url = data.url ? escapeHtml(data.url) : '';
        const openMarkup = url
            ? `<a href="${url}" class="admin-notification-open" data-notification-id="${id}">Open</a>`
            : '';

        return `
            <article class="admin-notification-item ${unread ? 'unread' : ''}" data-notification-id="${id}">
                <div class="admin-notification-item-icon">${escapeHtml(data.icon || '🔔')}</div>
                <div class="admin-notification-item-copy">
                    <strong>${escapeHtml(data.title || 'NextPlay Notification')}</strong>
                    <p>${escapeHtml(data.message || '')}</p>
                    <small>${escapeHtml(notification.created_at_human || formatTime(notification.created_at))}</small>
                    <div class="admin-notification-item-actions">
                        ${openMarkup}
                        ${unread ? `<button type="button" data-admin-mark-read="${id}">Mark read</button>` : ''}
                    </div>
                </div>
            </article>`;
    }

    function render() {
        if (!list) return;
        if (!notifications.length) {
            list.innerHTML = '<div class="admin-notification-empty">No notifications yet.</div>';
            return;
        }
        list.innerHTML = notifications.map(itemMarkup).join('');
    }

    function setPanelOpen(open) {
        panel?.classList.toggle('hidden', !open);
        widget.classList.toggle('is-open', open);
        bell?.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    async function request(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                ...(options.headers || {}),
            },
            ...options,
        });

        if (!response.ok) throw new Error(`Notification request failed (${response.status}).`);
        return response.json();
    }

    async function refresh() {
        try {
            const payload = await request(config.feedUrl);
            notifications = Array.isArray(payload.notifications) ? payload.notifications : [];
            updateCount(payload.unread_count);
            render();
        } catch (error) {
            if (status) status.textContent = 'Unable to load';
            console.warn(error);
        }
    }

    async function markRead(id) {
        if (!id) return;
        const url = String(config.readUrlTemplate || '').replace('__ID__', encodeURIComponent(id));
        if (!url) return;
        const payload = await request(url, { method: 'POST' });
        notifications = notifications.map((item) => item.id === id ? { ...item, read_at: new Date().toISOString() } : item);
        updateCount(payload.unread_count);
        render();
    }

    async function markAllRead() {
        const payload = await request(config.readAllUrl, { method: 'POST' });
        notifications = notifications.map((item) => ({ ...item, read_at: item.read_at || new Date().toISOString() }));
        updateCount(payload.unread_count);
        render();
    }

    function showRealtimeToast(notification) {
        const data = notification.data || {};
        const toast = document.getElementById('adminToast');
        if (!toast) return;
        toast.textContent = `${data.icon || '🔔'} ${data.title || 'Notification'}: ${data.message || ''}`;
        toast.classList.add('show');
        window.setTimeout(() => toast.classList.remove('show'), 5200);
    }

    function addRealtime(notification) {
        if (!notification?.id || notifications.some((item) => item.id === notification.id)) return;
        notifications = [{ ...notification, created_at_human: 'Just now' }, ...notifications].slice(0, 15);
        updateCount(unreadCount + 1);
        render();
        showRealtimeToast(notification);
    }

    bell?.addEventListener('click', () => {
        const opening = panel?.classList.contains('hidden');
        setPanelOpen(Boolean(opening));
        if (opening) refresh();
    });

    document.addEventListener('click', (event) => {
        if (!widget.contains(event.target)) {
            setPanelOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel?.classList.contains('hidden')) {
            setPanelOpen(false);
            bell?.focus();
        }
    });

    list?.addEventListener('click', async (event) => {
        const markButton = event.target.closest('[data-admin-mark-read]');
        const openLink = event.target.closest('.admin-notification-open');
        try {
            if (markButton) {
                await markRead(markButton.dataset.adminMarkRead);
            } else if (openLink) {
                await markRead(openLink.dataset.notificationId);
            }
        } catch (error) {
            console.warn(error);
        }
    });

    markAll?.addEventListener('click', async () => {
        try {
            await markAllRead();
        } catch (error) {
            console.warn(error);
        }
    });

    document.querySelectorAll('.admin-notification-page-mark-read').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                await markRead(button.dataset.notificationId);
                button.closest('.admin-notification-page-item')?.classList.remove('unread');
                button.remove();
            } catch (error) {
                console.warn(error);
            }
        });
    });

    if (config.pusherEnabled && window.Pusher && config.pusherKey) {
        try {
            const authOptions = {
                endpoint: config.pusherAuthUrl,
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            };
            const pusher = new window.Pusher(config.pusherKey, {
                cluster: config.pusherCluster,
                forceTLS: true,
                authEndpoint: config.pusherAuthUrl,
                auth: { headers: authOptions.headers },
                channelAuthorization: authOptions,
            });
            pusher.subscribe(`private-admin.user.${config.userId}`)
                .bind('admin-notification', addRealtime);
        } catch (error) {
            console.warn('Pusher notifications could not start. Polling remains active.', error);
        }
    }

    refresh();
    window.setInterval(refresh, Number(config.pollIntervalMs) || 60000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refresh();
    });
})();
