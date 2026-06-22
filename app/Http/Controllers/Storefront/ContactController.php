<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
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

        ContactMessage::create([
            ...$data,
            'status' => ContactMessage::STATUS_NEW,
            // Store non-reversible fingerprints instead of raw request metadata.
            'ip_hash' => $ip ? hash_hmac('sha256', $ip, $hashKey) : null,
            'user_agent_hash' => $userAgent !== '' ? hash_hmac('sha256', $userAgent, $hashKey) : null,
        ]);

        return back()
            ->with('status', 'Thanks—your message has been received. Our support team will review it and respond as soon as possible.')
            ->withHeaders(['Cache-Control' => 'no-store, private']);
    }
}
