<?php

namespace App\Services\Artwork;

use App\Models\ArtworkProof;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArtworkProofService
{
    public function currentForCustomer(int $userId): Builder
    {
        return ArtworkProof::query()->current()->whereHas('order', fn (Builder $query) => $query->where('user_id', $userId));
    }

    public function latestForItem(int $orderItemId): ?ArtworkProof
    {
        return ArtworkProof::query()->where('order_item_id', $orderItemId)->latest('version')->first();
    }

    public function publish(Order $order, OrderItem $item, UploadedFile $file, ?string $designName, ?string $notes): ArtworkProof
    {
        if ((int) $item->order_id !== (int) $order->id) {
            abort(404);
        }
        if (in_array($order->status, ['cancelled', 'completed'], true)) {
            throw ValidationException::withMessages(['proof' => 'New proofs cannot be issued for a closed order.']);
        }

        $path = $file->store('artwork-proofs/'.$order->id.'/'.$item->id, 'local');
        if ($path === false) {
            throw ValidationException::withMessages(['proof_file' => 'Unable to save the proof. Please try again.']);
        }

        try {
            return DB::transaction(function () use ($order, $item, $file, $path, $designName, $notes): ArtworkProof {
                // Serialize version allocation per order item, including the first upload.
                OrderItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
                $version = (int) ArtworkProof::query()->where('order_item_id', $item->id)->max('version') + 1;

                return ArtworkProof::create([
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'version' => $version,
                    'status' => ArtworkProof::PENDING,
                    'design_name' => $designName ?: $item->product_name,
                    'file_path' => $path,
                    'original_name' => basename($file->getClientOriginalName()),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => (int) $file->getSize(),
                    'designer_notes' => $notes,
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    public function decide(ArtworkProof $proof, string $decision, array $data = [], ?UploadedFile $reference = null): ArtworkProof
    {
        $referencePath = null;
        if ($reference) {
            $referencePath = $reference->store('artwork-revisions/'.$proof->id, 'local');
            if ($referencePath === false) {
                throw ValidationException::withMessages(['reference_file' => 'Unable to upload the reference file.']);
            }
        }

        try {
            return DB::transaction(function () use ($proof, $decision, $data, $reference, $referencePath): ArtworkProof {
                // Lock the item before the proof to prevent a decision racing an admin upload.
                OrderItem::query()->whereKey($proof->order_item_id)->lockForUpdate()->firstOrFail();
                $locked = ArtworkProof::query()->whereKey($proof->id)->lockForUpdate()->firstOrFail();
                $lastVersion = (int) ArtworkProof::query()->where('order_item_id', $locked->order_item_id)->max('version');
                if ($locked->version !== $lastVersion || $locked->status !== ArtworkProof::PENDING) {
                    throw ValidationException::withMessages(['proof' => 'This proof is no longer awaiting review. Refresh the page to see the latest version.']);
                }

                $order = Order::query()->findOrFail($locked->order_id);
                if (in_array($order->status, ['cancelled', 'completed', 'shipped', 'delivered'], true)) {
                    throw ValidationException::withMessages(['proof' => 'This order can no longer be reviewed.']);
                }
                if ($decision === ArtworkProof::APPROVED && $order->payment_status !== 'paid') {
                    throw ValidationException::withMessages(['proof' => 'Artwork can be approved for production after payment is confirmed.']);
                }
                if ($decision === ArtworkProof::REVISION) {
                    $requested = ArtworkProof::query()->where('order_item_id', $locked->order_item_id)
                        ->where('status', ArtworkProof::REVISION)->count();
                    $max = max(0, (int) config('storefront.artwork.max_revisions', 2));
                    if ($max !== 0 && $requested >= $max) {
                        throw ValidationException::withMessages(['proof' => 'The included revision limit has been reached. Please contact support.']);
                    }
                }

                $attributes = match ($decision) {
                    ArtworkProof::APPROVED => ['status' => ArtworkProof::APPROVED, 'approved_at' => now()],
                    ArtworkProof::REVISION => [
                        'status' => ArtworkProof::REVISION,
                        'customer_feedback' => $data['feedback'],
                        'change_types' => $data['change_types'] ?? [],
                        'priority' => $data['priority'] ?? 'normal',
                        'reference_path' => $referencePath,
                        'reference_name' => $reference ? basename($reference->getClientOriginalName()) : null,
                        'reference_mime_type' => $reference?->getMimeType(),
                        'revision_requested_at' => now(),
                    ],
                    ArtworkProof::REJECTED => [
                        'status' => ArtworkProof::REJECTED,
                        'customer_feedback' => $data['feedback'],
                        'rejected_at' => now(),
                    ],
                    default => throw new \InvalidArgumentException('Unsupported artwork decision.'),
                };
                $locked->update($attributes);
                return $locked;
            });
        } catch (\Throwable $exception) {
            if ($referencePath) {
                Storage::disk('local')->delete($referencePath);
            }
            throw $exception;
        }
    }

    public function stream(ArtworkProof $proof, string $type, bool $download): StreamedResponse
    {
        if ($type === 'reference') {
            abort_unless(filled($proof->reference_path), 404);
            $path = $proof->reference_path;
            $name = $proof->reference_name;
            $mime = $proof->reference_mime_type;
        } else {
            $path = $proof->file_path;
            $name = $proof->original_name;
            $mime = $proof->mime_type;
        }

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404, 'The requested proof file is unavailable.');
        $stream = $disk->readStream($path);
        abort_if($stream === false, 404);
        $safeName = str_replace(["\r", "\n", '"', '\\'], '', basename((string) ($name ?: 'artwork-proof')));
        $mime = in_array($mime, ['image/png', 'image/jpeg', 'image/webp', 'application/pdf'], true) ? $mime : 'application/octet-stream';
        $disposition = $download ? 'attachment' : 'inline';

        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition.'; filename="'.$safeName.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "sandbox; default-src 'none'",
        ]);
    }
}
