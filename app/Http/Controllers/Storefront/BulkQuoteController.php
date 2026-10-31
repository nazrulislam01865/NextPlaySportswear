<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreBulkQuoteRequest;
use App\Services\BulkQuote\BulkQuoteGuestService;
use App\Models\BulkQuoteRequest;
use App\Models\CountryCallingCode;
use App\Models\User;
use App\Models\Product;
use App\Models\ShippingMethod;
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
        $countryCallingCodes = CountryCallingCode::query()->active()->ordered()->get();

        // Match the four existing quote preferences to the images that admins
        // upload in Master Data > Shipping Methods. Do not change form values.
        $shippingAliases = [
            'standard' => ['standard', 'standard-shipping', 'standard-delivery'],
            'express' => ['express', 'express-shipping', 'express-delivery', 'expedited', 'expedited-shipping'],
            'rush' => ['rush', 'rush-shipping', 'rush-delivery', 'rush-review', 'rush-event-delivery-review', 'rush-delivery-if-available'],
            'recommend' => ['recommend', 'recommended', 'recommend-best-option', 'recommend-best-shipping-option'],
        ];
        $shippingOptionIcons = [];
        $shippingMethodsWithImages = ShippingMethod::query()
            ->active()
            ->where(static function ($query): void {
                $query->whereNotNull('image_path')->orWhereNotNull('image_url');
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['name', 'code', 'image_path', 'image_url']);

        foreach ($shippingMethodsWithImages as $shippingMethod) {
            $code = Str::slug((string) $shippingMethod->code);
            $name = Str::slug((string) $shippingMethod->name);
            $imageUrl = $shippingMethod->imageUrl();
            if (! $imageUrl) {
                continue;
            }
            foreach ($shippingAliases as $preference => $aliases) {
                // Prefer the actual master code/name; a descriptive suffix is OK
                // (e.g. "standard-shipping-uk") but do not invent icon images.
                $matches = in_array($code, $aliases, true) || in_array($name, $aliases, true)
                    || collect($aliases)->contains(static fn (string $alias): bool =>
                        str_starts_with($code, $alias.'-') || str_starts_with($name, $alias.'-'));
                if (! isset($shippingOptionIcons[$preference]) && $matches) {
                    $shippingOptionIcons[$preference] = $imageUrl;
                    break;
                }
            }
        }

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
            'countryCallingCodes',
            'shippingOptionIcons',
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
        $items = $validated['quote_products'] ?? null;
        unset($validated['quote_products_json'], $validated['quote_products'], $validated['quote_artworks']);
        $validated['product_information'] = ! empty($validated['product_information']['product_id'])
            ? $productInformation->validateAndSnapshot($validated['product_information']) : null;

        // All requested items live in the existing JSON column. Catalogue IDs are
        // verified and snapshotted against current published products (not client labels).
        $itemArtwork = null;
        if (is_array($items)) {
            $selectedProducts = [];
            foreach ($items as $index => $item) {
                $snapshot = ($item['mode'] ?? '') === 'existing'
                    ? $productInformation->validateAndSnapshot([
                        'product_id' => (int) ($item['catalog_product_id'] ?? 0),
                        'options' => $item['options'] ?? [],
                        'sizes' => $item['sizeQuantities'] ?? [],
                        'notes' => mb_substr((string) ($item['customization'] ?? ''), 0, 500),
                    ]) : null;
                $artwork = null;
                if ($request->hasFile("quote_artworks.$index")) {
                    $file = $request->file("quote_artworks.$index");
                    $artwork = [
                        'path' => $file->store('bulk-quotes', 'local'),
                        'disk' => 'local',
                        'original_name' => Str::limit($file->getClientOriginalName(), 190, ''),
                        'mime_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ];
                    $itemArtwork ??= $artwork;
                }
                // Use verified catalogue option labels, not arbitrary JSON fields,
                // for a catalogue product's material, colour, and size summary.
                $configuredValue = static function (array $options, string $pattern): string {
                    foreach ($options as $option) {
                        if (preg_match($pattern, (string) ($option['label'] ?? '')) !== 1) continue;
                        return trim((string) ($option['value'] ?? implode(', ', $option['values'] ?? [])));
                    }
                    return '';
                };
                $confirmedSizes = $snapshot
                    ? collect($snapshot['size_groups'] ?? [])->flatMap(static fn (array $group) =>
                        collect($group['sizes'] ?? [])->map(static fn (array $size) =>
                            (string) $size['size'].': '.(int) $size['quantity']))->implode('  |  ')
                    : (string) ($item['sizes'] ?? '');
                $selectedProducts[] = [
                    'name' => $snapshot['product_name'] ?? $item['name'],
                    'quantity' => (int) $item['quantity'],
                    'unit' => 'pcs',
                    'category' => $snapshot ? 'Catalogue product' : 'New product',
                    'product_id' => $snapshot['product_id'] ?? null,
                    'sku' => $snapshot['sku'] ?? null,
                    'description' => $item['description'] ?? '',
                    'material' => $snapshot ? $configuredValue($snapshot['options'], '/fabric|material/i') : ($item['material'] ?? ''),
                    'colors' => $snapshot ? $configuredValue($snapshot['options'], '/colou?r/i') : ($item['colors'] ?? ''),
                    'sizes' => $confirmedSizes,
                    'budget' => $snapshot ? '' : ($item['budget'] ?? ''),
                    'customizations' => $item['customizations'] ?? [],
                    'customization_details' => $item['customization'] ?? '',
                    'options' => $snapshot['options'] ?? [],
                    'size_groups' => $snapshot['size_groups'] ?? [],
                    'artwork' => $artwork,
                ];
            }
            $validated['product_information'] = ['selected_products' => $selectedProducts];
        }

        $countryCallingCode = CountryCallingCode::query()
            ->active()
            ->findOrFail((int) $validated['phone_country_code_id']);
        $phone = trim((string) $validated['phone']);
        $validated['phone'] = str_starts_with($phone, $countryCallingCode->dial_code)
            ? $phone
            : trim($countryCallingCode->dial_code.' '.$phone);
        unset($validated['phone_country_code_id']);

        $attachment = $itemArtwork;
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
