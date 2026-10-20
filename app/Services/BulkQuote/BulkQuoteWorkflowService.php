<?php

namespace App\Services\BulkQuote;

use App\Models\BulkQuoteActivity;
use App\Models\BulkQuoteRequest;
use App\Models\User;
use App\Support\AdminRbac;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BulkQuoteWorkflowService
{
    /**
     * @param array<string,mixed> $data
     */
    public function update(BulkQuoteRequest $quote, User $actor, array $data): BulkQuoteRequest
    {
        $assigneeId = filled($data['assigned_to'] ?? null) ? (int) $data['assigned_to'] : null;
        $this->validateAssignee($assigneeId);

        return DB::transaction(function () use ($quote, $actor, $data, $assigneeId): BulkQuoteRequest {
            $quote->refresh();

            $before = Arr::only($quote->getAttributes(), [
                'status', 'priority', 'assigned_to', 'quoted_amount', 'quote_currency',
                'admin_note', 'last_contacted_at', 'closed_at', 'closed_by',
            ]);

            $newStatus = (string) $data['status'];
            // Guest quote pricing and publication are controlled exclusively by
            // the versioned Publish Quotation action, never by the workflow form.
            if ($quote->user_id === null && $quote->quotation_version > 0 &&
                array_key_exists('quoted_amount', $data) && $data['quoted_amount'] !== null &&
                round((float) $data['quoted_amount'], 2) !== round((float) $quote->quoted_amount, 2)) {
                throw ValidationException::withMessages([
                    'quoted_amount' => 'To change a published quotation price, publish a new quotation version with updated files.',
                ]);
            }
            if ($quote->user_id === null && $newStatus === BulkQuoteRequest::STATUS_QUOTATION_SENT && ! $quote->quotation_version) {
                throw ValidationException::withMessages([
                    'status' => 'Upload and publish the guest quotation first to mark it as sent.',
                ]);
            }

            $isClosed = in_array($newStatus, [
                BulkQuoteRequest::STATUS_REJECTED,
                BulkQuoteRequest::STATUS_CONVERTED,
                BulkQuoteRequest::STATUS_CLOSED,
            ], true);

            $quote->forceFill([
                'status' => $newStatus,
                'priority' => (string) $data['priority'],
                'assigned_to' => $assigneeId,
                'quoted_amount' => $quote->user_id === null && $quote->quotation_version > 0 ? $quote->quoted_amount : ($data['quoted_amount'] ?? null),
                'quote_currency' => strtoupper((string) $data['quote_currency']),
                'admin_note' => $data['admin_note'] ?? null,
                'last_contacted_at' => $data['last_contacted_at'] ?? null,
                'closed_at' => $isClosed ? ($quote->closed_at ?: now()) : null,
                'closed_by' => $isClosed ? ($quote->closed_by ?: $actor->id) : null,
            ])->save();

            $changed = $quote->getChanges();
            $meaningfulChanges = Arr::only($changed, array_keys($before));

            if ($meaningfulChanges !== []) {
                BulkQuoteActivity::query()->create([
                    'bulk_quote_request_id' => $quote->id,
                    'actor_id' => $actor->id,
                    'action' => ($before['status'] ?? null) !== $newStatus ? 'status_updated' : 'details_updated',
                    'from_status' => $before['status'] ?? null,
                    'to_status' => $newStatus,
                    'note' => array_key_exists('admin_note', $meaningfulChanges) ? ($data['admin_note'] ?? null) : null,
                    'metadata' => [
                        'before' => $before,
                        'changed_fields' => array_keys($meaningfulChanges),
                    ],
                    'occurred_at' => now(),
                ]);
            }

            return $quote->fresh(['assignedAdmin', 'closedByAdmin']);
        });
    }

    private function validateAssignee(?int $assigneeId): void
    {
        if ($assigneeId === null) {
            return;
        }

        $assignee = User::query()->find($assigneeId);

        if (! $assignee instanceof User || ! $assignee->is_active || ! AdminRbac::roleIsAdmin($assignee->role)) {
            throw ValidationException::withMessages([
                'assigned_to' => 'The selected assignee must be an active admin user.',
            ]);
        }
    }
}
