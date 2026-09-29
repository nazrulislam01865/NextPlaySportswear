<?php

namespace App\Services\Catalog;

use App\Http\Requests\Admin\ShippingDeliveryPageRequest;
use App\Support\PublicMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ShippingDeliveryPageMediaService
{
    /**
     * @param array<string, mixed> $current
     * @param array<string, mixed> $payload
     * @return array{payload:array<string,mixed>,new_paths:array<int,string>,old_paths:array<int,string>,delete_after_commit:array<int,string>}
     */
    public function prepare(ShippingDeliveryPageRequest $request, array $current, array $payload): array
    {
        $mutation = [
            'payload' => $payload,
            'new_paths' => [],
            'old_paths' => [],
            'delete_after_commit' => [],
        ];

        try {
            foreach ($this->slots() as $slot) {
                $currentPath = data_get($current, $slot['data_path']);
                $currentPath = is_string($currentPath) && trim($currentPath) !== ''
                    ? PublicMedia::normalizePath($currentPath)
                    : null;
                data_set($mutation['payload'], $slot['data_path'], $currentPath);

                $file = $request->file($slot['file']);
                if ($file instanceof UploadedFile) {
                    $newPath = $file->store($slot['directory'], 'public');
                    if (! is_string($newPath) || $newPath === '') {
                        throw new RuntimeException('Unable to store Shipping & Delivery page media.');
                    }
                    $newPath = PublicMedia::normalizePath($newPath);
                    data_set($mutation['payload'], $slot['data_path'], $newPath);
                    $mutation['new_paths'][] = $newPath;

                    if ($currentPath !== null && $currentPath !== $newPath && $this->isShippingDeliveryOwnedPath($currentPath)) {
                        $mutation['old_paths'][] = $currentPath;
                        $mutation['delete_after_commit'][] = $currentPath;
                    }
                    continue;
                }

                if ($request->boolean($slot['remove'])) {
                    data_set($mutation['payload'], $slot['data_path'], null);
                    if ($currentPath !== null && $this->isShippingDeliveryOwnedPath($currentPath)) {
                        $mutation['old_paths'][] = $currentPath;
                        $mutation['delete_after_commit'][] = $currentPath;
                    }
                }
            }
        } catch (Throwable $exception) {
            $this->rollback($mutation);
            throw $exception;
        }

        foreach (['new_paths', 'old_paths', 'delete_after_commit'] as $key) {
            $mutation[$key] = array_values(array_unique($mutation[$key]));
        }

        return $mutation;
    }

    /** @param array<string, mixed> $mutation */
    public function rollback(array $mutation): void
    {
        $this->deleteOwnedPaths($mutation['new_paths'] ?? []);
    }

    /** @param array<string, mixed> $mutation */
    public function commitCleanup(array $mutation): void
    {
        $this->deleteOwnedPaths($mutation['delete_after_commit'] ?? $mutation['old_paths'] ?? []);
    }

    public function isShippingDeliveryOwnedPath(string $path): bool
    {
        $normalized = PublicMedia::normalizePath($path);
        if (! str_starts_with($normalized, 'shipping-delivery-page/') || str_contains($normalized, "\0")) {
            return false;
        }
        foreach (explode('/', $normalized) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return count(explode('/', $normalized)) >= 3;
    }

    /** @return array<int, array{file:string,remove:string,data_path:string,directory:string}> */
    private function slots(): array
    {
        $slots = [];
        for ($i = 0; $i < 2; $i++) {
            $slots[] = [
                'file' => 'info_card_icon_'.$i,
                'remove' => 'remove_info_card_icon_'.$i,
                'data_path' => 'info_cards.cards.'.$i.'.icon_path',
                'directory' => 'shipping-delivery-page/info-cards/icons',
            ];
        }
        $slots[] = [
            'file' => 'notice_icon',
            'remove' => 'remove_notice_icon',
            'data_path' => 'notice.icon_path',
            'directory' => 'shipping-delivery-page/notice/icons',
        ];
        for ($i = 0; $i < 4; $i++) {
            $slots[] = [
                'file' => 'checklist_icon_'.$i,
                'remove' => 'remove_checklist_icon_'.$i,
                'data_path' => 'address_checklist.items.'.$i.'.icon_path',
                'directory' => 'shipping-delivery-page/address-checklist/icons',
            ];
        }

        return $slots;
    }

    /** @param array<int, mixed> $paths */
    private function deleteOwnedPaths(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            if (! is_string($path) || ! $this->isShippingDeliveryOwnedPath($path)) {
                continue;
            }
            Storage::disk('public')->delete(PublicMedia::normalizePath($path));
        }
    }
}
