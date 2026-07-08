<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreBulkQuoteRequest;
use App\Models\BulkQuoteRequest;
use App\Services\AdminNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BulkQuoteController extends Controller
{
    public function create(): View
    {
        $faqItems = [
            ['question' => 'What is the minimum order quantity?', 'answer' => 'It depends on the product. Team uniforms usually start from 10+ pieces, while event apparel and promotional items may have different minimums.'],
            ['question' => 'Can I request a quote without final artwork?', 'answer' => 'Yes. You can upload a rough reference or describe the design. Final artwork can be confirmed later.'],
            ['question' => 'How fast will I receive the quote?', 'answer' => 'Most quote requests are reviewed within 24 hours on business days. Large or complex orders may need more clarification.'],
            ['question' => 'Do you support names and numbers?', 'answer' => 'Yes. For jerseys and uniforms, you can include player names, numbers, sizes, and roster files.'],
            ['question' => 'Can you help choose the right product?', 'answer' => 'Yes. Share your event, sport, or budget and we can suggest suitable product options.'],
            ['question' => 'Is rush delivery available?', 'answer' => 'Rush delivery depends on the item, quantity, artwork readiness, and shipping location. Add your needed date in the form.'],
        ];

        $structuredData = [[
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($faqItems)->map(fn (array $item): array => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ])->all(),
        ]];

        return view('storefront.bulk-quote.create', compact('faqItems', 'structuredData'));
    }

    public function store(StoreBulkQuoteRequest $request, AdminNotificationService $notifications): RedirectResponse
    {
        $validated = $request->validated();
        unset($validated['company']);

        $attachment = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('bulk-quotes', 'public');

            $attachment = [
                'path' => $path,
                'original_name' => Str::limit($file->getClientOriginalName(), 190, ''),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ];
        }

        $hashKey = (string) config('app.key');
        $ip = $request->ip();
        $userAgent = trim((string) $request->userAgent());

        $quote = BulkQuoteRequest::create([
            ...$validated,
            'reference' => $this->makeReference(),
            'attachment' => $attachment,
            'status' => BulkQuoteRequest::STATUS_NEW,
            'ip_hash' => $ip ? hash_hmac('sha256', $ip, $hashKey) : null,
            'user_agent_hash' => $userAgent !== '' ? hash_hmac('sha256', $userAgent, $hashKey) : null,
        ]);

        if (Schema::hasTable('notifications')) {
            $notifications->notifyAdmins([
                'title' => 'Bulk quote request received',
                'message' => 'New bulk quote request '.$quote->reference.' from '.$quote->full_name.' for '.$quote->organization.'.',
                'category' => 'quote',
                'icon' => '📋',
                'resource' => 'bulk-quote-request',
                'resource_id' => $quote->id,
                'resource_name' => $quote->reference,
                'resource_code' => $quote->estimated_quantity,
                'action' => 'created',
                'occurred_at' => now()->toIso8601String(),
            ]);
        }

        return redirect()
            ->route('quote.request')
            ->with('status', 'Thanks—your bulk quote request has been received. Reference: '.$quote->reference.'. Our team will review it and contact you soon.')
            ->withHeaders(['Cache-Control' => 'no-store, private']);
    }

    private function makeReference(): string
    {
        do {
            $reference = 'BQ-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (BulkQuoteRequest::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
