<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBulkQuoteRequest;
use App\Models\AdminRole;
use App\Models\BulkQuoteRequest;
use App\Models\User;
use App\Services\BulkQuote\BulkQuoteWorkflowService;
use App\Services\Integrations\FlowTrack\FlowTrackInquirySyncManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkQuoteRequestController extends Controller
{
    public function index(Request $request): View
    {
        $quotes = BulkQuoteRequest::query()
            ->select([
                'id',
                'reference',
                'full_name',
                'organization',
                'email',
                'phone',
                'product_type',
                'estimated_quantity',
                'sizes_needed',
                'country',
                'state_province',
                'needed_by',
                'status',
                'assigned_to',
                'priority',
                'quoted_amount',
                'quote_currency',
                'flowtrack_sync_status',
                'flowtrack_inquiry_number',
                'created_at',
            ])
            ->with('assignedAdmin:id,name');

        $search = trim((string) $request->query('q'));
        if ($search !== '') {
            $quotes->where(function ($query) use ($search): void {
                $query->where('reference', 'like', '%'.$search.'%')
                    ->orWhere('full_name', 'like', '%'.$search.'%')
                    ->orWhere('organization', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('product_type', 'like', '%'.$search.'%')
                    ->orWhere('country', 'like', '%'.$search.'%');
            });
        }

        $status = trim((string) $request->query('status'));
        if (array_key_exists($status, BulkQuoteRequest::statuses())) {
            $quotes->where('status', $status);
        }

        $priority = trim((string) $request->query('priority'));
        if (array_key_exists($priority, BulkQuoteRequest::priorities())) {
            $quotes->where('priority', $priority);
        }

        $syncStatus = trim((string) $request->query('sync_status'));
        if ($syncStatus !== '') {
            $quotes->where('flowtrack_sync_status', $syncStatus);
        }

        $country = trim((string) $request->query('country'));
        if ($country !== '') {
            $quotes->where('country', $country);
        }

        $assignee = trim((string) $request->query('assigned_to'));
        if ($assignee !== '' && ctype_digit($assignee)) {
            $quotes->where('assigned_to', (int) $assignee);
        }

        $syncStatusOptions = BulkQuoteRequest::query()
            ->whereNotNull('flowtrack_sync_status')
            ->where('flowtrack_sync_status', '<>', '')
            ->distinct()
            ->orderBy('flowtrack_sync_status')
            ->pluck('flowtrack_sync_status');

        $countries = BulkQuoteRequest::query()
            ->whereNotNull('country')
            ->where('country', '<>', '')
            ->distinct()
            ->orderBy('country')
            ->pluck('country');

        return view('admin.bulk-quotes.index', [
            'quotes' => $quotes->latest('created_at')->paginate($this->adminPerPage(25))->withQueryString(),
            'statusOptions' => BulkQuoteRequest::statuses(),
            'priorityOptions' => BulkQuoteRequest::priorities(),
            'syncStatusOptions' => $syncStatusOptions,
            'countries' => $countries,
            'assignees' => $this->activeAdmins(),
        ]);
    }

    public function show(BulkQuoteRequest $bulkQuote): View
    {
        $bulkQuote->loadMissing(['user', 'assignedAdmin', 'closedByAdmin']);
        $activities = $bulkQuote->activities()
            ->with('actor:id,name')
            ->limit(50)
            ->get();

        return view('admin.bulk-quotes.show', [
            'bulkQuote' => $bulkQuote,
            'activities' => $activities,
            'statusOptions' => BulkQuoteRequest::statuses(),
            'priorityOptions' => BulkQuoteRequest::priorities(),
            'assignees' => $this->activeAdmins(),
        ]);
    }

    public function update(
        UpdateBulkQuoteRequest $request,
        BulkQuoteRequest $bulkQuote,
        BulkQuoteWorkflowService $workflow,
    ): RedirectResponse {
        $workflow->update($bulkQuote, $request->user('admin'), $request->validated());

        return back()->with('status', 'Bulk quote workflow updated successfully.');
    }

    public function retrySync(
        Request $request,
        BulkQuoteRequest $bulkQuote,
        FlowTrackInquirySyncManager $sync,
    ): RedirectResponse {
        abort_unless($request->user('admin')?->canAdmin('orders.manage'), 403, 'Order management access is required.');

        if ($bulkQuote->flowtrack_sync_status === BulkQuoteRequest::FLOWTRACK_SYNC_SYNCING) {
            return back()->with('status', 'FlowTrack synchronization is already in progress.');
        }

        $sync->dispatch($bulkQuote);
        $bulkQuote->refresh();

        if ($bulkQuote->flowtrack_sync_status === BulkQuoteRequest::FLOWTRACK_SYNC_FAILED) {
            return back()->withErrors([
                'flowtrack' => $bulkQuote->flowtrack_sync_error ?: 'FlowTrack synchronization could not be started.',
            ]);
        }

        if ($bulkQuote->flowtrack_sync_status === BulkQuoteRequest::FLOWTRACK_SYNC_DISABLED) {
            return back()->with('status', 'FlowTrack integration is disabled. The request remains safely stored in NextPlay.');
        }

        return back()->with('status', 'FlowTrack synchronization has been queued or completed successfully.');
    }

    public function attachment(BulkQuoteRequest $bulkQuote): StreamedResponse
    {
        $attachment = is_array($bulkQuote->attachment) ? $bulkQuote->attachment : [];
        $path = trim((string) ($attachment['path'] ?? ''));
        abort_if($path === '', 404, 'This bulk quote does not have an attachment.');

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404, 'The bulk quote attachment could not be found.');

        $stream = $disk->readStream($path);
        abort_if($stream === false, 404, 'The bulk quote attachment could not be opened.');

        $originalName = basename((string) ($attachment['original_name'] ?? 'bulk-quote-attachment'));
        $safeName = str_replace(["\r", "\n", '"'], '', $originalName);
        $mimeType = trim((string) ($attachment['mime_type'] ?? ''));
        if ($mimeType === '' || $mimeType === 'application/octet-stream') {
            $mimeType = (string) ($disk->mimeType($path) ?: 'application/octet-stream');
        }

        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="'.$safeName.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function activeAdmins()
    {
        $roleSlugs = AdminRole::query()->where('is_active', true)->pluck('slug');

        return User::query()
            ->select(['id', 'name', 'role'])
            ->where('is_active', true)
            ->whereIn('role', $roleSlugs)
            ->orderBy('name')
            ->get();
    }
}
