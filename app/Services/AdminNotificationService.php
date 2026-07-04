<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminNotificationService
{
    public function __construct(private readonly PusherChannelsService $pusher)
    {
    }

    /** @return Collection<int, User> */
    public function adminUsers(): Collection
    {
        if (! Schema::hasTable('users')) {
            return collect();
        }

        return User::query()
            ->where('is_active', true)
            ->where('role', '<>', 'customer')
            ->get()
            ->filter(fn (User $user): bool => $user->isAdmin() && $user->canAdmin('products.view'))
            ->values();
    }

    public function notifyAdmins(array $data): int
    {
        if (! Schema::hasTable('notifications')) {
            return 0;
        }

        $sent = 0;
        $normalized = $this->normalizeData($data);

        foreach ($this->adminUsers()->unique('id') as $user) {
            $notificationId = (string) Str::uuid();
            $now = now();

            DB::table('notifications')->insert([
                'id' => $notificationId,
                'type' => 'nextplay.admin',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->pusher->triggerAdminUser((int) $user->id, 'admin-notification', [
                'id' => $notificationId,
                'data' => $normalized,
                'read_at' => null,
                'created_at' => $now->toIso8601String(),
            ]);

            $sent++;
        }

        return $sent;
    }

    public function productChanged(Product $product, string $action, ?User $actor = null, ?string $url = null): int
    {
        $action = in_array($action, ['created', 'updated', 'deleted', 'duplicated'], true) ? $action : 'updated';
        $actorName = trim((string) ($actor?->name ?: $actor?->email ?: 'An administrator'));
        $productName = trim((string) ($product->name ?: 'Unnamed product'));
        $sku = trim((string) ($product->sku ?: ''));

        $title = match ($action) {
            'created' => 'Product added',
            'deleted' => 'Product deleted',
            'duplicated' => 'Product duplicated',
            default => 'Product edited',
        };

        $verb = match ($action) {
            'created' => 'added',
            'deleted' => 'deleted',
            'duplicated' => 'duplicated',
            default => 'updated',
        };

        $icon = match ($action) {
            'created' => '＋',
            'deleted' => '×',
            'duplicated' => '⧉',
            default => '✎',
        };

        $message = $actorName.' '.$verb.' product “'.$productName.'”'.($sku !== '' ? ' ('.$sku.').' : '.');

        return $this->notifyAdmins([
            'title' => $title,
            'message' => $message,
            'category' => 'product',
            'icon' => $icon,
            'url' => $url ?: '',
            'actor_name' => $actorName,
            'actor_id' => $actor?->id,
            'resource' => 'product',
            'resource_id' => $product->id,
            'resource_name' => $productName,
            'resource_code' => $sku,
            'action' => $action,
            'occurred_at' => now()->toIso8601String(),
        ]);
    }

    private function normalizeData(array $data): array
    {
        return [
            'title' => trim((string) ($data['title'] ?? 'NextPlay Notification')),
            'message' => trim((string) ($data['message'] ?? '')),
            'category' => trim((string) ($data['category'] ?? 'system')),
            'icon' => trim((string) ($data['icon'] ?? '🔔')),
            'url' => trim((string) ($data['url'] ?? '')),
            'actor_name' => trim((string) ($data['actor_name'] ?? '')),
            'actor_id' => $data['actor_id'] ?? null,
            'resource' => trim((string) ($data['resource'] ?? '')),
            'resource_id' => $data['resource_id'] ?? null,
            'resource_name' => trim((string) ($data['resource_name'] ?? '')),
            'resource_code' => trim((string) ($data['resource_code'] ?? '')),
            'action' => trim((string) ($data['action'] ?? '')),
            'occurred_at' => (string) ($data['occurred_at'] ?? now()->toIso8601String()),
        ];
    }
}
