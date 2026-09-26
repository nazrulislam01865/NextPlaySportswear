<?php

namespace App\Services\Email;

use App\Contracts\EmailService;
use App\Data\EmailMessage;
use App\Models\BulkQuoteRequest;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OrderChangeRequest;
use App\Models\OrderReturnRequest;
use App\Models\OrderShipment;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

final class TransactionalEmailManager
{
    public function __construct(private readonly EmailService $emails)
    {
    }

    public function emailVerification(User $user): void
    {
        if (! $this->emails->enabled()) {
            throw new RuntimeException('Transactional email is currently disabled.');
        }

        $expiresInMinutes = max(
            5,
            (int) config('security.email_verification.expire_minutes', 60)
        );

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes($expiresInMinutes),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $message = new EmailMessage(
            key: 'customer.email-verification',
            recipients: $this->customerRecipient($user->email, $user->name),
            subject: 'Verify your NextPlay Sportswear email address',
            heading: 'Verify your email address',
            introLines: [
                'Thanks for creating or updating your NextPlay Sportswear customer account.',
                'Confirm that this email address belongs to you before using protected account and checkout features.',
            ],
            details: [
                'Account Email' => (string) $user->email,
                'Link Expires' => $expiresInMinutes.' minutes',
            ],
            actionText: 'Verify Email Address',
            actionUrl: $verificationUrl,
            outroLines: [
                'If you did not create or update this account, you can ignore this email.',
                'For your security, do not forward this verification link to anyone.',
            ],
            metadata: ['user_id' => $user->id],
        );

        if ((bool) config('transactional_email.critical.email_verification_sync', true)) {
            $this->emails->sendNow($message);

            return;
        }

        $this->emails->queue($message);
    }

    public function passwordReset(User $user, string $token): void
    {
        if (! $this->emails->enabled()) {
            throw new RuntimeException('Transactional email is currently disabled.');
        }

        $expiresInMinutes = max(
            1,
            (int) config('auth.passwords.users.expire', 60)
        );

        $message = new EmailMessage(
            key: 'customer.password-reset',
            recipients: $this->customerRecipient($user->email, $user->name),
            subject: 'Reset your NextPlay Sportswear password',
            heading: 'Reset your password',
            introLines: [
                'We received a request to reset the password for your NextPlay Sportswear customer account.',
                'Use the secure button below to choose a new password. If you did not request this, you can ignore this email.',
            ],
            details: [
                'Account Email' => (string) $user->email,
                'Link Expires' => $expiresInMinutes.' minutes',
            ],
            actionText: 'Reset Password',
            actionUrl: route('password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]),
            outroLines: [
                'This reset link can only be used once.',
                'For your security, never forward this reset link to anyone.',
            ],
            metadata: ['user_id' => $user->id],
        );

        if ((bool) config('transactional_email.critical.password_reset_sync', true)) {
            $this->emails->sendNow($message);

            return;
        }

        $this->emails->queue($message);
    }

    public function welcome(User $user): bool
    {
        return $this->emails->safelyQueue(new EmailMessage(
            key: 'customer.welcome',
            recipients: $this->customerRecipient($user->email, $user->name),
            subject: 'Welcome to NextPlay Sportswear',
            heading: 'Your email is verified - welcome to NextPlay',
            introLines: [
                'Thanks for verifying your email address. Your NextPlay Sportswear customer account is now ready to use.',
                'You can securely access your account, saved details, order history, quotes, and checkout.',
            ],
            actionText: 'Open My Account',
            actionUrl: route('account.dashboard'),
            outroLines: [
                'If you did not create this account, please contact our support team.',
            ],
            metadata: ['user_id' => $user->id],
        ));
    }

    public function passwordChanged(User $user): void
    {
        $this->emails->safelyQueue(new EmailMessage(
            key: 'customer.password-changed',
            recipients: $this->customerRecipient($user->email, $user->name),
            subject: 'Your NextPlay Sportswear password was changed',
            heading: 'Your password was changed',
            introLines: [
                'The password for your NextPlay Sportswear customer account was changed successfully.',
                'If you made this change, no further action is required. If you did not, contact support immediately.',
            ],
            actionText: 'Contact Support',
            actionUrl: route('contact'),
            metadata: ['user_id' => $user->id],
        ));
    }

    public function emailAddressChanged(User $user, string $oldEmail): void
    {
        $oldEmail = strtolower(trim($oldEmail));
        $newEmail = strtolower(trim((string) $user->email));

        if ($oldEmail === $newEmail) {
            return;
        }

        $this->emails->safelyQueue(new EmailMessage(
            key: 'customer.email-changed-old-address',
            recipients: $this->customerRecipient($oldEmail, $user->name),
            subject: 'Your NextPlay Sportswear email address was changed',
            heading: 'Your account email changed',
            introLines: [
                'The email address on your NextPlay Sportswear customer account was changed.',
                'If you did not make this change, contact our support team immediately.',
            ],
            details: [
                'Previous Email' => $oldEmail,
                'New Email' => $newEmail,
            ],
            actionText: 'Contact Support',
            actionUrl: route('contact'),
            metadata: ['user_id' => $user->id],
        ));
    }

    public function contactReceived(ContactMessage $contact): void
    {
        $this->emails->safelyQueue(new EmailMessage(
            key: 'contact.customer-confirmation',
            recipients: $this->customerRecipient($contact->email, $contact->name),
            subject: 'We received your NextPlay support message',
            heading: 'Your message has been received',
            introLines: [
                'Thanks for contacting NextPlay Sportswear. Our support team has received your message and will review it as soon as possible.',
            ],
            details: array_filter([
                'Topic' => $this->label($contact->topic),
                'Order Number' => $contact->order_number,
            ], static fn (mixed $value): bool => filled($value)),
            actionText: 'Visit Help Center',
            actionUrl: route('faq'),
            metadata: ['contact_message_id' => $contact->id],
        ));

        $this->queueInternal(
            recipientKey: 'support',
            message: new EmailMessage(
                key: 'contact.internal-alert',
                recipients: [],
                subject: 'New website support message',
                heading: 'New customer support message',
                introLines: ['A new contact form submission has been stored in the NextPlay backend.'],
                details: array_filter([
                    'Name' => $contact->name,
                    'Email' => $contact->email,
                    'Phone' => $contact->phone,
                    'Topic' => $this->label($contact->topic),
                    'Order Number' => $contact->order_number,
                    'Message' => Str::limit((string) $contact->message, 1200),
                ], static fn (mixed $value): bool => filled($value)),
                replyTo: $contact->email,
                replyToName: $contact->name,
                metadata: ['contact_message_id' => $contact->id],
            ),
        );
    }

    public function bulkQuoteReceived(BulkQuoteRequest $quote): void
    {
        $this->emails->safelyQueue(new EmailMessage(
            key: 'quote.customer-confirmation',
            recipients: $this->customerRecipient($quote->email, $quote->full_name),
            subject: 'Quote request '.$quote->reference.' received',
            heading: 'We received your bulk quote request',
            introLines: [
                'Thanks for requesting a custom sportswear quote. Our team will review the requirements and contact you using the details you provided.',
            ],
            details: array_filter([
                'Reference' => $quote->reference,
                'Product Type' => $quote->product_type,
                'Estimated Quantity' => $quote->estimated_quantity,
                'Needed By' => $quote->needed_by?->format('M j, Y'),
            ], static fn (mixed $value): bool => filled($value)),
            actionText: 'Visit NextPlay Sportswear',
            actionUrl: route('home'),
            metadata: ['bulk_quote_request_id' => $quote->id],
        ));

        $this->queueInternal(
            recipientKey: 'sales',
            message: new EmailMessage(
                key: 'quote.internal-alert',
                recipients: [],
                subject: 'New bulk quote '.$quote->reference,
                heading: 'New bulk quote request',
                introLines: ['A new bulk quote request has been stored and is ready for review.'],
                details: array_filter([
                    'Reference' => $quote->reference,
                    'Customer' => $quote->full_name,
                    'Organization' => $quote->organization,
                    'Email' => $quote->email,
                    'Phone' => $quote->phone,
                    'Product Type' => $quote->product_type,
                    'Estimated Quantity' => $quote->estimated_quantity,
                    'Budget' => $quote->budget_range,
                    'Needed By' => $quote->needed_by?->format('M j, Y'),
                ], static fn (mixed $value): bool => filled($value)),
                replyTo: $quote->email,
                replyToName: $quote->full_name,
                metadata: ['bulk_quote_request_id' => $quote->id],
            ),
        );
    }

    public function orderPlaced(Order $order): void
    {
        $order->loadMissing('items');

        $details = [
            'Order Number' => $order->order_number,
            'Order Status' => $order->statusLabel(),
            'Payment Status' => $order->paymentStatusLabel(),
            'Items' => (string) $order->total_quantity,
            'Order Total' => $this->money($order->grand_total, $order->currency),
        ];

        $this->emails->safelyQueue(new EmailMessage(
            key: 'order.customer-placed',
            recipients: $this->customerRecipient($order->customer_email, $order->customer_name),
            subject: 'Order '.$order->order_number.' received',
            heading: 'Your order has been received',
            introLines: [
                'Thanks for your order. We have saved your order securely and will keep its status updated as it moves through payment, design, production, and fulfillment.',
            ],
            details: $details,
            actionText: 'View Order',
            actionUrl: route('account.orders.show', $order),
            metadata: ['order_id' => $order->id],
        ));

        $this->queueInternal(
            recipientKey: 'orders',
            message: new EmailMessage(
                key: 'order.internal-placed',
                recipients: [],
                subject: 'New order '.$order->order_number,
                heading: 'New storefront order',
                introLines: ['A new customer order has been persisted and is ready for the next operational step.'],
                details: [
                    ...$details,
                    'Customer' => $order->customer_name,
                    'Customer Email' => $order->customer_email,
                ],
                actionText: 'Open Admin Order',
                actionUrl: route('admin.orders.show', $order),
                replyTo: $order->customer_email,
                replyToName: $order->customer_name,
                metadata: ['order_id' => $order->id],
            ),
        );
    }

    public function orderUpdated(
        Order $order,
        string $oldStatus,
        string $oldPaymentStatus,
        string $oldFulfillmentStatus,
    ): void {
        if (
            $oldStatus === $order->status
            && $oldPaymentStatus === $order->payment_status
            && $oldFulfillmentStatus === $order->fulfillment_status
        ) {
            return;
        }

        $this->emails->safelyQueue(new EmailMessage(
            key: 'order.customer-status-updated',
            recipients: $this->customerRecipient($order->customer_email, $order->customer_name),
            subject: 'Order '.$order->order_number.' status update',
            heading: 'Your order status changed',
            introLines: ['There is a new status update for your NextPlay Sportswear order.'],
            details: [
                'Order Number' => $order->order_number,
                'Order Status' => $order->statusLabel(),
                'Payment Status' => $order->paymentStatusLabel(),
                'Fulfillment' => $order->fulfillmentStatusLabel(),
            ],
            actionText: 'View Order',
            actionUrl: route('account.orders.show', $order),
            metadata: ['order_id' => $order->id],
        ));
    }

    public function shipmentUpdated(
        OrderShipment $shipment,
        ?string $oldStatus = null,
        ?string $oldTrackingNumber = null,
        ?string $oldTrackingUrl = null,
        bool $created = false,
    ): void
    {
        $shipment->loadMissing('order');
        $order = $shipment->order;

        if (! $order instanceof Order) {
            return;
        }

        if (
            ! $created
            && $oldStatus === $shipment->status
            && trim((string) $oldTrackingNumber) === trim((string) $shipment->tracking_number)
            && trim((string) $oldTrackingUrl) === trim((string) $shipment->tracking_url)
        ) {
            return;
        }

        if ($created && $shipment->status === 'preparing' && blank($shipment->tracking_number)) {
            return;
        }

        $details = array_filter([
            'Order Number' => $order->order_number,
            'Shipment' => $shipment->shipment_number,
            'Status' => $shipment->statusLabel(),
            'Carrier' => $shipment->carrier,
            'Service' => $shipment->service,
            'Tracking Number' => $shipment->tracking_number,
            'Estimated Delivery' => $shipment->estimated_delivery_at?->format('M j, Y'),
        ], static fn (mixed $value): bool => filled($value));

        $actionUrl = filled($shipment->tracking_url)
            ? (string) $shipment->tracking_url
            : route('account.orders.shipments.show', [
                'order' => $order,
                'shipment' => $shipment,
            ]);

        $this->emails->safelyQueue(new EmailMessage(
            key: 'shipment.customer-status-updated',
            recipients: $this->customerRecipient($order->customer_email, $order->customer_name),
            subject: 'Shipment update for '.$order->order_number,
            heading: $created ? 'Your shipment is being prepared' : 'Your shipment status changed',
            introLines: ['We have a new shipping update for your NextPlay Sportswear order.'],
            details: $details,
            actionText: filled($shipment->tracking_url) ? 'Track Shipment' : 'View Shipment',
            actionUrl: $actionUrl,
            metadata: [
                'order_id' => $order->id,
                'shipment_id' => $shipment->id,
            ],
        ));
    }

    public function returnSubmitted(OrderReturnRequest $returnRequest): void
    {
        $returnRequest->loadMissing(['order', 'user']);
        $order = $returnRequest->order;
        $user = $returnRequest->user;

        if (! $order instanceof Order || ! $user instanceof User) {
            return;
        }

        $typeLabel = Str::headline($returnRequest->type);

        $this->emails->safelyQueue(new EmailMessage(
            key: 'return.customer-submitted',
            recipients: $this->customerRecipient($user->email, $user->name),
            subject: $typeLabel.' request '.$returnRequest->return_number.' received',
            heading: 'Your '.$typeLabel.' request was received',
            introLines: ['Our team will review your request and update its status in your account.'],
            details: [
                'Request Number' => $returnRequest->return_number,
                'Order Number' => $order->order_number,
                'Status' => $returnRequest->statusLabel(),
            ],
            actionText: 'View Request',
            actionUrl: route('account.returns.show', $returnRequest),
            metadata: ['return_request_id' => $returnRequest->id],
        ));

        $this->queueInternal(
            recipientKey: 'returns',
            message: new EmailMessage(
                key: 'return.internal-submitted',
                recipients: [],
                subject: 'New '.$returnRequest->type.' request '.$returnRequest->return_number,
                heading: 'New return or exchange request',
                introLines: ['A customer submitted a new request that is ready for eligibility review.'],
                details: [
                    'Request Number' => $returnRequest->return_number,
                    'Type' => $typeLabel,
                    'Order Number' => $order->order_number,
                    'Customer' => $user->name,
                    'Customer Email' => $user->email,
                    'Status' => $returnRequest->statusLabel(),
                ],
                actionText: 'Open Admin Request',
                actionUrl: route('admin.returns.show', $returnRequest),
                replyTo: $user->email,
                replyToName: $user->name,
                metadata: ['return_request_id' => $returnRequest->id],
            ),
        );
    }

    public function returnUpdated(
        OrderReturnRequest $returnRequest,
        string $oldStatus,
        ?string $oldRefundStatus = null,
    ): void {
        $returnRequest->loadMissing(['order', 'user', 'refunds']);
        $currentRefund = $returnRequest->refunds->first();
        $currentRefundStatus = $currentRefund?->status;

        if ($oldStatus === $returnRequest->status && $oldRefundStatus === $currentRefundStatus) {
            return;
        }

        $order = $returnRequest->order;
        $user = $returnRequest->user;

        if (! $order instanceof Order || ! $user instanceof User) {
            return;
        }

        $details = [
            'Request Number' => $returnRequest->return_number,
            'Order Number' => $order->order_number,
            'Status' => $returnRequest->statusLabel(),
        ];

        if ((float) ($returnRequest->approved_amount ?? 0) > 0) {
            $details['Approved Amount'] = $this->money($returnRequest->approved_amount, $order->currency);
        }

        if ($currentRefund) {
            $details['Refund Status'] = Str::headline((string) $currentRefund->status);
            $details['Refund Amount'] = $this->money($currentRefund->amount, $currentRefund->currency);
        }

        $this->emails->safelyQueue(new EmailMessage(
            key: 'return.customer-status-updated',
            recipients: $this->customerRecipient($user->email, $user->name),
            subject: 'Request '.$returnRequest->return_number.' status update',
            heading: 'Your return or exchange request changed',
            introLines: ['There is a new update on your return or exchange request.'],
            details: $details,
            actionText: 'View Request',
            actionUrl: route('account.returns.show', $returnRequest),
            metadata: ['return_request_id' => $returnRequest->id],
        ));
    }

    public function changeRequestSubmitted(OrderChangeRequest $changeRequest): void
    {
        $changeRequest->loadMissing(['order', 'user']);
        $order = $changeRequest->order;
        $user = $changeRequest->user;

        if (! $order instanceof Order || ! $user instanceof User) {
            return;
        }

        $typeLabel = $changeRequest->type === 'cancel' ? 'Cancellation' : 'Order change';

        $this->emails->safelyQueue(new EmailMessage(
            key: 'order-request.customer-submitted',
            recipients: $this->customerRecipient($user->email, $user->name),
            subject: $typeLabel.' request '.$changeRequest->request_number.' received',
            heading: 'Your request was received',
            introLines: ['Our team will review the request before any production or fulfillment changes are applied.'],
            details: [
                'Request Number' => $changeRequest->request_number,
                'Order Number' => $order->order_number,
                'Request Type' => $typeLabel,
                'Status' => Str::headline($changeRequest->status),
            ],
            actionText: 'View Order',
            actionUrl: route('account.orders.show', $order),
            metadata: ['change_request_id' => $changeRequest->id],
        ));

        $this->queueInternal(
            recipientKey: 'orders',
            message: new EmailMessage(
                key: 'order-request.internal-submitted',
                recipients: [],
                subject: $typeLabel.' request '.$changeRequest->request_number,
                heading: 'New customer order request',
                introLines: ['A customer submitted an order request that needs review.'],
                details: [
                    'Request Number' => $changeRequest->request_number,
                    'Order Number' => $order->order_number,
                    'Request Type' => $typeLabel,
                    'Customer' => $user->name,
                    'Customer Email' => $user->email,
                    'Status' => Str::headline($changeRequest->status),
                ],
                actionText: 'Open Admin Order',
                actionUrl: route('admin.orders.show', $order),
                replyTo: $user->email,
                replyToName: $user->name,
                metadata: ['change_request_id' => $changeRequest->id],
            ),
        );
    }

    public function changeRequestUpdated(OrderChangeRequest $changeRequest, string $oldStatus): void
    {
        if ($oldStatus === $changeRequest->status) {
            return;
        }

        $changeRequest->loadMissing(['order', 'user']);
        $order = $changeRequest->order;
        $user = $changeRequest->user;

        if (! $order instanceof Order || ! $user instanceof User) {
            return;
        }

        $this->emails->safelyQueue(new EmailMessage(
            key: 'order-request.customer-status-updated',
            recipients: $this->customerRecipient($user->email, $user->name),
            subject: 'Request '.$changeRequest->request_number.' status update',
            heading: 'Your order request changed',
            introLines: ['There is a new update on your order change or cancellation request.'],
            details: [
                'Request Number' => $changeRequest->request_number,
                'Order Number' => $order->order_number,
                'Status' => Str::headline($changeRequest->status),
            ],
            actionText: 'View Order',
            actionUrl: route('account.orders.show', $order),
            metadata: ['change_request_id' => $changeRequest->id],
        ));
    }

    /**
     * @return array<int, array{email: string, name: string|null}>
     */
    private function customerRecipient(?string $email, ?string $name = null): array
    {
        return [[
            'email' => trim((string) $email),
            'name' => filled($name) ? trim((string) $name) : null,
        ]];
    }

    private function queueInternal(string $recipientKey, EmailMessage $message): void
    {
        $address = trim((string) config('transactional_email.recipients.'.$recipientKey, ''));

        if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        $message->recipients = [[
            'email' => $address,
            'name' => config('transactional_email.brand.name'),
        ]];

        $this->emails->safelyQueue($message);
    }

    private function money(mixed $amount, ?string $currency): string
    {
        return strtoupper((string) ($currency ?: 'USD')).' '.number_format((float) $amount, 2);
    }

    private function label(?string $value): ?string
    {
        return filled($value) ? Str::headline((string) $value) : null;
    }
}
