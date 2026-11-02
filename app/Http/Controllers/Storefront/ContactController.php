<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Services\Storefront\ContactPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
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
        $data = $request->safe()->except('company');
        $hashKey = (string) config('app.key');
        $ip = $request->ip();
        $userAgent = trim((string) $request->userAgent());

        $contact = ContactMessage::create([
            ...$data,

            'status' => ContactMessage::STATUS_NEW,

            'ip_hash' => $ip
                ? hash_hmac(
                    'sha256',
                    $ip,
                    $hashKey
                )
                : null,

            'user_agent_hash' => $userAgent !== ''
                ? hash_hmac(
                    'sha256',
                    $userAgent,
                    $hashKey
                )
                : null,
        ]);

        $this->emails->contactReceived($contact);

        return back()
            ->with('status', 'Thanks—your message has been received. Our support team will review it and respond as soon as possible.')
            ->withHeaders(['Cache-Control' => 'no-store, private']);
    }
}
