<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreNewsletterSubscriptionRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class NewsletterController extends Controller
{
    public function store(StoreNewsletterSubscriptionRequest $request): JsonResponse|RedirectResponse
    {
        $email = (string) $request->validated('email');
        $hashKey = (string) config('app.key');
        $ip = $request->ip();
        $userAgent = trim((string) $request->userAgent());

        NewsletterSubscriber::updateOrCreate(
            ['email' => $email],
            [
                'status' => NewsletterSubscriber::STATUS_ACTIVE,
                'source' => (string) ($request->validated('source') ?: 'homepage-newsletter'),
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
                'ip_hash' => $ip ? hash_hmac('sha256', $ip, $hashKey) : null,
                'user_agent_hash' => $userAgent !== '' ? hash_hmac('sha256', $userAgent, $hashKey) : null,
            ]
        );

        $message = 'Thanks — you are on the list for team offers and kit ideas.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
            ], 201)->withHeaders(['Cache-Control' => 'no-store, private']);
        }

        return back()
            ->with('newsletter_status', $message)
            ->withHeaders(['Cache-Control' => 'no-store, private']);
    }
}
