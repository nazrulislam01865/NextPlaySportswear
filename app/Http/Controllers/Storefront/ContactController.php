<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreContactMessageRequest;
use App\Models\ContactMessage;
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
    public function index(): View
    {
        return view('storefront.content.contact');
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
