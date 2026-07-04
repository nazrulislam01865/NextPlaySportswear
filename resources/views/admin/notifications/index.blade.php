<x-layouts.admin
    title="Notifications"
    eyebrow="Administration"
    subtitle="Product activity alerts from the admin system."
    compact-header
>
    <div class="space-y-5 admin-notification-page">
        <section class="rounded-[1.35rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <form method="GET" action="{{ route('admin.notifications.index') }}" class="grid flex-1 gap-3 sm:grid-cols-[minmax(0,1fr)_180px_auto]">
                    <div>
                        <label class="admin-label" for="notificationSearch">Search</label>
                        <input id="notificationSearch" name="q" value="{{ $filters['search'] ?? '' }}" class="admin-input" placeholder="Search product, actor or action">
                    </div>
                    <div>
                        <label class="admin-label" for="notificationReadStatus">Status</label>
                        <select id="notificationReadStatus" name="read_status" class="admin-input">
                            <option value="">All notifications</option>
                            <option value="unread" @selected(($filters['readStatus'] ?? '') === 'unread')>Unread</option>
                            <option value="read" @selected(($filters['readStatus'] ?? '') === 'read')>Read</option>
                        </select>
                    </div>
                    <div class="flex gap-2 sm:self-end">
                        <button class="btn btn-dark" type="submit">Filter</button>
                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-light">Clear</a>
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                    @csrf
                    <button class="btn btn-light" type="submit">Mark all read</button>
                </form>
            </div>
        </section>

        <section class="rounded-[1.35rem] border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
            <div class="admin-notification-page-list">
                @forelse($notifications as $notification)
                    @php($data = is_array($notification->data) ? $notification->data : [])
                    <article class="admin-notification-page-item {{ $notification->read_at ? '' : 'unread' }}">
                        <div class="admin-notification-page-icon">{{ $data['icon'] ?? '🔔' }}</div>
                        <div class="admin-notification-page-copy">
                            <div class="admin-notification-page-title-row">
                                <strong>{{ $data['title'] ?? 'NextPlay Notification' }}</strong>
                                @unless($notification->read_at)<span class="admin-notification-page-badge">New</span>@endunless
                            </div>
                            <p>{{ $data['message'] ?? '' }}</p>
                            <small>{{ optional($notification->created_at)->format('d M Y, h:i A') }}</small>
                        </div>
                        <div class="admin-notification-page-actions">
                            @if(!empty($data['url']))
                                <a class="btn btn-light" href="{{ $data['url'] }}">Open</a>
                            @endif
                            @unless($notification->read_at)
                                <button
                                    type="button"
                                    class="btn btn-light admin-notification-page-mark-read"
                                    data-notification-id="{{ $notification->id }}"
                                >Mark read</button>
                            @endunless
                        </div>
                    </article>
                @empty
                    <div class="admin-notification-empty">No notifications yet.</div>
                @endforelse
            </div>

            @if(method_exists($notifications, 'links'))
                <div class="admin-pagination mt-5">{{ $notifications->links() }}</div>
            @endif
        </section>
    </div>
</x-layouts.admin>
