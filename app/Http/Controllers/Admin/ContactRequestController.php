<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactRequestController extends Controller
{
    public function index(Request $request): View
    {
        $stringFilter = static function (string $key) use ($request): string {
            $value = $request->query($key, '');
            return is_string($value) ? trim($value) : '';
        };
        $filters = [
            'q' => mb_substr($stringFilter('q'), 0, 100),
            'status' => mb_substr($stringFilter('status'), 0, 40),
            'topic' => mb_substr($stringFilter('topic'), 0, 80),
        ];

        $query = ContactMessage::query()->select([
            'id', 'name', 'email', 'phone', 'topic', 'order_number',
            'status', 'attachment_name', 'created_at',
        ]);

        // The message body is encrypted: never load/decrypt it in a paginated list.
        if ($filters['q'] !== '') {
            $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['q']);
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('order_number', 'like', $like);
            });
        }

        if (array_key_exists($filters['status'], ContactMessage::statuses())) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists($filters['topic'], ContactMessage::topics())) {
            $query->where('topic', $filters['topic']);
        }

        // One compact aggregate query instead of a separate COUNT per status.
        $stats = ContactMessage::query()->toBase()->selectRaw(
            "COUNT(*) AS total, "
            ."SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS new_count, "
            ."SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS in_review_count, "
            ."SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) AS finished_count",
            [ContactMessage::STATUS_NEW, ContactMessage::STATUS_IN_REVIEW, ContactMessage::STATUS_RESOLVED, ContactMessage::STATUS_CLOSED]
        )->first();

        return view('admin.contact-requests.index', [
            'requests' => $query->orderByDesc('id')
                ->paginate($this->adminPerPage(25))->withQueryString(),
            'filters' => $filters,
            'stats' => $stats,
            'statuses' => ContactMessage::statuses(),
            'topics' => ContactMessage::topics(),
        ]);
    }

    public function show(ContactMessage $contactMessage): View
    {
        return view('admin.contact-requests.show', [
            'contact' => $contactMessage,
            'statuses' => ContactMessage::statuses(),
            'canManage' => (bool) auth('admin')->user()?->canAdmin('contact_requests.manage'),
        ]);
    }

    public function update(Request $request, ContactMessage $contactMessage): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(ContactMessage::statuses()))],
            'internal_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $note = trim((string) ($validated['internal_note'] ?? ''));
        $note = $note === '' ? null : $note;

        // Avoid redundant writes, timestamp changes and model events on no-op saves.
        if ($contactMessage->status !== $validated['status'] || $contactMessage->internal_note !== $note) {
            $contactMessage->update([
                'status' => $validated['status'],
                'internal_note' => $note,
            ]);
        }

        return redirect()->route('admin.contact-requests.show', $contactMessage)
            ->with('status', 'Contact request updated successfully.');
    }

    /** Private original upload: only accessible through the permission-protected admin route. */
    public function attachment(ContactMessage $contactMessage): StreamedResponse
    {
        $path = (string) $contactMessage->attachment_path;
        abort_unless($path !== '' && str_starts_with($path, 'contact-messages/'.$contactMessage->getKey().'/')
            && ! str_contains($path, '..') && Storage::disk('local')->exists($path), 404);

        $filename = basename(str_replace('\\', '/', (string) ($contactMessage->attachment_name ?: 'contact-attachment')));
        $filename = str_replace(["\r", "\n", '"'], '', $filename);

        return Storage::disk('local')->download($path, $filename, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
