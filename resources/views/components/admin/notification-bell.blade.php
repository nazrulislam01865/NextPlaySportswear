<div class="admin-notification-widget" id="adminNotificationWidget">
    <button
        type="button"
        class="admin-notification-bell"
        id="adminNotificationBell"
        aria-label="Open notifications"
        aria-expanded="false"
        aria-controls="adminNotificationPanel"
    >
        <span aria-hidden="true">🔔</span>
        <span class="admin-notification-count hidden" id="adminNotificationCount">0</span>
    </button>

    <section class="admin-notification-panel hidden" id="adminNotificationPanel" aria-label="Notifications">
        <header class="admin-notification-panel-head">
            <div>
                <strong>Notifications</strong>
                <small id="adminNotificationStatus">Loading…</small>
            </div>
            <button type="button" class="admin-notification-text-btn" id="adminMarkAllRead">Mark all read</button>
        </header>

        <div class="admin-notification-list" id="adminNotificationList">
            <div class="admin-notification-empty">Loading notifications…</div>
        </div>

        <footer class="admin-notification-panel-foot">
            <a href="{{ route('admin.notifications.index') }}">View all notifications</a>
        </footer>
    </section>
</div>
