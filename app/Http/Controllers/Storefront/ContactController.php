<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Services\Storefront\ContactPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use App\Services\Email\TransactionalEmailManager;

class ContactController extends Controller
{
    //Email Constructor
    public function __construct(
        private readonly TransactionalEmailManager $emails
    ) {
    }
    public function index(ContactPageService $service): View
    {
        $content = $service->settings();
        $seo = [
            'title' => (string) data_get($content, 'seo.meta_title', 'Contact Us | ' . config('storefront.name', 'NextPlay Sportswear')),
            'description' => (string) data_get($content, 'seo.meta_description', 'Questions about a product or order? Send us a message.'),
            'robots' => 'index, follow',
            'canonical' => route('contact'),
            'og_title' => (string) data_get($content, 'seo.meta_title', 'Contact Us | ' . config('storefront.name', 'NextPlay Sportswear')),
            'og_description' => (string) data_get($content, 'seo.meta_description', 'Questions about a product or order? Send us a message.'),
            'schema_type' => 'ContactPage',
        ];

        return view('storefront.content.contact', compact('content', 'seo'));
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['company', 'g-recaptcha-response', 'attachment']);
        $hashKey = (string) config('app.key');
        $ip = $request->ip();
        $userAgent = trim((string) $request->userAgent());

        // A contact attachment is private, never published under /storage or /media.
        // Keep the database and uploaded file together; delete any orphan on failure.
        $storedPath = null;
        try {
            $contact = DB::transaction(function () use ($request, $data, $ip, $hashKey, $userAgent, &$storedPath): ContactMessage {
                $contact = ContactMessage::create([
                    ...$data,
                    'status' => ContactMessage::STATUS_NEW,
                    'ip_hash' => $ip ? hash_hmac('sha256', $ip, $hashKey) : null,
                    'user_agent_hash' => $userAgent !== '' ? hash_hmac('sha256', $userAgent, $hashKey) : null,
                ]);

                if ($request->hasFile('attachment')) {
                    $file = $request->file('attachment');
                    $storedPath = $file->store('contact-messages/'.$contact->id, 'local');
                    if (! is_string($storedPath) || $storedPath === '') {
                        throw new RuntimeException('The contact attachment could not be saved.');
                    }

                    $contact->update([
                        'attachment_path' => $storedPath,
                        'attachment_name' => Str::limit(basename(str_replace('\\', '/', $file->getClientOriginalName())), 255, ''),
                        'attachment_mime' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
                        'attachment_size' => $file->getSize(),
                    ]);
                }

                return $contact;
            });
        } catch (Throwable $exception) {
            if (is_string($storedPath) && $storedPath !== '') {
                Storage::disk('local')->delete($storedPath);
            }
            report($exception);

            return back()->withInput($request->except('attachment', 'g-recaptcha-response'))
                ->withErrors(['attachment' => 'Your message could not be saved. Please try again.']);
        }

        $this->emails->contactReceived($contact);

        return back()
            ->with('status', 'Thanks—your message has been received. Our support team will review it and respond as soon as possible.')
            ->withHeaders(['Cache-Control' => 'no-store, private']);
    }
}
