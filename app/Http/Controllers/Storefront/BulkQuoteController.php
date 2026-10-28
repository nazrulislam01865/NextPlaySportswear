<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreBulkQuoteRequest;
use App\Services\BulkQuote\BulkQuoteGuestService;
use App\Models\BulkQuoteBudgetRange;
use App\Models\BulkQuoteRequest;
use App\Models\CountryCallingCode;
use App\Models\User;
use App\Models\Product;
use App\Services\BulkQuote\QuoteProductInformation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\AdminNotificationService;
use App\Services\Email\TransactionalEmailManager;
use App\Services\Integrations\FlowTrack\FlowTrackInquirySyncManager;
use App\Services\Shipping\RuralAreaSurchargeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BulkQuoteController extends Controller
{
    public function __construct(
        private readonly TransactionalEmailManager $emails,
    ) {
    }

    public function create(Request $request, QuoteProductInformation $productInformation): View
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

        $countryCallingCodes = CountryCallingCode::query()->active()->ordered()->get();
        $budgetRanges = BulkQuoteBudgetRange::query()->active()->ordered()->get();

        // The product-page Request Quote link already supplies a product slug.
        // Load it only when that link is used (or when restoring a failed POST).
        $selectedProduct = null;
        $selectedId = (int) old('product_information.product_id', 0);
        if ($selectedId > 0) {
            $product = $productInformation->find($selectedId);
            $selectedProduct = $product ? $productInformation->describe($product) : null;
        } elseif (is_string($requestedProduct = $request->query('product'))
            && $requestedProduct !== '' && mb_strlen($requestedProduct) <= 200) {
            // Product pages link by ID, while existing shared/SEO links can still use slugs.
            // Both reuse the exact same published-product payload as the search picker.
            $product = ctype_digit($requestedProduct)
                ? $productInformation->find((int) $requestedProduct)
                : $productInformation->findBySlug($requestedProduct);
            $selectedProduct = $product ? $productInformation->describe($product) : null;
        }

        return view('storefront.bulk-quote.create', compact(
            'faqItems',
            'structuredData',
            'countryCallingCodes',
            'budgetRanges',
            'selectedProduct',
        ));
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $term = $request->query('q', '');
        $query = is_string($term) ? trim($term) : '';
        if (mb_strlen($query) < 2 || mb_strlen($query) > 90) {
            return response()->json(['products' => []]);
        }

        $products = Product::query()->published()
            ->where(function ($builder) use ($query): void {
                $builder->where('name', 'like', '%'.addcslashes($query, '%_\\').'%')
                    ->orWhere('sku', 'like', '%'.addcslashes($query, '%_\\').'%');
            })
            ->with('images')
            ->orderBy('name')->limit(10)->get();

        return response()->json(['products' => $products->map(fn (Product $product): array => [
            'id' => $product->id,
            'name' => (string) $product->name,
            'sku' => (string) ($product->sku ?? ''),
            'image' => $product->primaryImageUrl(),
        ])->all()])->header('Cache-Control', 'no-store');
    }

    public function productDetails(int $product, QuoteProductInformation $information): JsonResponse
    {
        $found = $information->find($product);
        if (! $found) {
            return response()->json(['message' => 'This product is no longer available.'], 404);
        }

        return response()->json(['product' => $information->describe($found)])
            ->header('Cache-Control', 'no-store');
    }

    public function store(
        StoreBulkQuoteRequest $request,
        AdminNotificationService $notifications,
        FlowTrackInquirySyncManager $flowTrackSync,
        BulkQuoteGuestService $guest,
        RuralAreaSurchargeService $remoteAreas,
        QuoteProductInformation $productInformation,
    ): RedirectResponse {
        $validated = $request->validated();
        unset($validated['company'], $validated['g-recaptcha-response']);
        $validated['product_information'] = ! empty($validated['product_information']['product_id'])
            ? $productInformation->validateAndSnapshot($validated['product_information']) : null;

        $countryCallingCode = CountryCallingCode::query()
            ->active()
            ->findOrFail((int) $validated['phone_country_code_id']);
        $phone = trim((string) $validated['phone']);
        $validated['phone'] = str_starts_with($phone, $countryCallingCode->dial_code)
            ? $phone
            : trim($countryCallingCode->dial_code.' '.$phone);
        unset($validated['phone_country_code_id']);

        $attachment = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('bulk-quotes', 'local');

            $attachment = [
                'path' => $path,
                'disk' => 'local',
                'original_name' => Str::limit($file->getClientOriginalName(), 190, ''),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ];
        }

        $hashKey = (string) config('app.key');
        $ip = $request->ip();
        $userAgent = trim((string) $request->userAgent());
        $user = $request->user();
        $customer = $user instanceof User ? $user : null;

        $accessToken = $customer === null ? $guest->newToken() : null;
        $quote = BulkQuoteRequest::create([
            'guest_access_hash' => $accessToken !== null ? hash('sha256', $accessToken) : null,
            ...$validated,
            'reference' => $this->makeReference(),
            'is_remote_area' => filled($validated['postal_code'] ?? null)
                ? $remoteAreas->resolve(
                    $validated['postal_code'], $validated['country'] ?? 'United States',
                    $validated['state_province'] ?? null,
                ) !== null
                : null,
            'user_id' => $customer?->id,
            'customer_account' => $this->customerAccountSnapshot($customer, $validated),
            'attachment' => $attachment,
            'status' => BulkQuoteRequest::STATUS_NEW,
            'flowtrack_sync_status' => (bool) config('flowtrack.enabled')
                ? BulkQuoteRequest::FLOWTRACK_SYNC_PENDING
                : BulkQuoteRequest::FLOWTRACK_SYNC_DISABLED,
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

        $this->emails->bulkQuoteReceived($quote, $accessToken);

        // The request has already been safely stored in NextPlay. FlowTrack
        // availability must never roll back or falsify a successful submission.
        $flowTrackSync->dispatch($quote);

        if ($accessToken !== null) {
            return redirect()->route('quote.guest.success', ['reference' => $quote->reference, 'token' => $accessToken])
                ->withHeaders(['Cache-Control' => 'no-store, private']);
        }

        if ($customer !== null) {
            return redirect()->route('account.bulk-quotes.show', $quote->reference)
                ->with('status', 'Your bulk quote request has been submitted. We will notify you when your quotation is ready.');
        }

        return redirect()
            ->route('quote.request')
            ->with('status', 'Thanks—your bulk quote request has been received. Reference: '.$quote->reference.'. Our team will review it and contact you soon.')
            ->withHeaders(['Cache-Control' => 'no-store, private']);
    }

    /**
     * @param array<string,mixed> $validated
     * @return array<string,mixed>
     */
    private function customerAccountSnapshot(?User $user, array $validated): array
    {
        return [
            'is_registered' => $user !== null,
            'source_user_id' => $user?->id,
            'name' => (string) ($user?->name ?: ($validated['full_name'] ?? '')),
            'email' => (string) ($user?->email ?: ($validated['email'] ?? '')),
            'phone' => $user?->phone ?: ($validated['phone'] ?? null),
            'company_name' => $user?->company_name,
            'preferred_sport' => $user?->preferred_sport,
        ];
    }

    private function makeReference(): string
    {
        do {
            $reference = 'BQ-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (BulkQuoteRequest::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
