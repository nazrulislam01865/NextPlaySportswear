<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PusherChannelsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::guard('admin')->user();
        $search = trim((string) $request->query('q', ''));
        $readStatus = trim((string) $request->query('read_status', ''));

        if ($user && Schema::hasTable('notifications')) {
            $query = $user->notifications();

            if ($search !== '') {
                $query->where('data', 'like', '%'.$search.'%');
            }

            if ($readStatus === 'read') {
                $query->whereNotNull('read_at');
            } elseif ($readStatus === 'unread') {
                $query->whereNull('read_at');
            }

            $notifications = $query->latest()->paginate($this->adminPerPage(20))->withQueryString();
        } else {
            $notifications = collect();
        }

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'filters' => compact('search', 'readStatus'),
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $user = Auth::guard('admin')->user();

        if (! $user || ! Schema::hasTable('notifications')) {
            return response()->json(['notifications' => [], 'unread_count' => 0]);
        }

        $notifications = $user->notifications()
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => $this->serialize($notification))
            ->values();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function markRead(string $notification): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user, 403);

        $record = $user->notifications()->whereKey($notification)->firstOrFail();
        if ($record->read_at === null) {
            $record->markAsRead();
        }

        return response()->json([
            'ok' => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user, 403);

        $user->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'unread_count' => 0]);
        }

        return back()->with('status', 'All notifications marked as read.');
    }

    public function pusherAuth(Request $request, PusherChannelsService $pusher): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'socket_id' => ['required', 'regex:/^\d+\.\d+$/'],
            'channel_name' => ['required', 'string', 'max:200'],
        ]);

        $expectedChannel = 'private-admin.user.'.(int) $user->id;
        abort_unless(hash_equals($expectedChannel, $validated['channel_name']), 403);
        abort_unless($pusher->enabled(), 503, 'Pusher is not configured.');

        return response()->json($pusher->authenticate($validated['socket_id'], $validated['channel_name']));
    }

    private function serialize(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'data' => $notification->data,
            'read_at' => optional($notification->read_at)->toIso8601String(),
            'created_at' => optional($notification->created_at)->toIso8601String(),
            'created_at_human' => optional($notification->created_at)->diffForHumans(),
        ];
    }
}
