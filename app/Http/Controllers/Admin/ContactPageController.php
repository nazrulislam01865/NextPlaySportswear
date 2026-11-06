<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContactPageRequest;
use App\Services\Storefront\ContactPageService;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class ContactPageController extends Controller
{
    public function __construct(
        private readonly ContactPageService $service
    ) {
    }

    public function edit(): View
    {
        return view('admin.contact-page.edit', [
            'content' => $this->service->settings(),
            'canManage' => (bool) (auth('admin')->user()?->canAdmin('contact_page.manage') ?? false),
        ]);
    }

    /** Download a Contact Us attachment only after authenticated admin permission checks. */
    public function downloadAttachment(ContactMessage $contactMessage): StreamedResponse
    {
        $path = (string) $contactMessage->attachment_path;
        $prefix = 'contact-messages/'.$contactMessage->id.'/';
        abort_unless($path !== '' && str_starts_with($path, $prefix)
            && Storage::disk('local')->exists($path), 404);

        $name = basename(str_replace('\\', '/', (string) ($contactMessage->attachment_name ?: 'attachment')));
        $name = str_replace(["\r", "\n", '"'], '', $name);
        return Storage::disk('local')->download($path, $name, [
            'Content-Type' => $contactMessage->attachment_mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function update(ContactPageRequest $request): RedirectResponse
    {
        try {
            $this->service->update(
                $request->validated(),
                auth('admin')->id()
            );
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.contact-page.edit')
                ->withInput()
                ->withErrors(['contact_page' => 'The Contact Us page could not be saved: ' . $e->getMessage()]);
        }

        return redirect()
            ->route('admin.contact-page.edit')
            ->with('status', 'Contact Us page updated successfully.');
    }
}

