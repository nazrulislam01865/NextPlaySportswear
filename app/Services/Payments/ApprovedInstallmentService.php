<?php

namespace App\Services\Payments;

use App\Models\BulkQuoteRequest;
use App\Models\Order;
use App\Models\OrderPaymentInstallment;
use App\Models\OrderInstallmentSchedule;
use App\Payments\Exceptions\PaymentGatewayException;
use App\Payments\Money;
use Illuminate\Support\Facades\DB;

/**
 * Only structured terms already accepted by the customer may become payable.
 * This service has no HTTP/administrator schedule-creation endpoint.
 * Never parse a free-text deposit percentage into a monetary obligation.
 */
final class ApprovedInstallmentService
{
    public function ensureForOrder(Order $order): ?OrderInstallmentSchedule
    {
        if (! config('payments.installments.enabled', false)) {
            return null;
        }
        $existing = $order->installmentSchedule()->first();
        if ($existing) {
            return $existing;
        }
        if (data_get($order->information, 'source') !== 'guest_bulk_quote') {
            return null;
        }
        $quoteId = (int) data_get($order->information, 'bulk_quote_id', 0);
        $quote = BulkQuoteRequest::query()->whereKey($quoteId)->first();
        if (! $quote || $quote->status !== BulkQuoteRequest::STATUS_CONVERTED
            || $quote->guest_decision !== 'create_order'
            || (int) $quote->converted_order_id !== (int) $order->id
            || (int) $quote->quotation_version !== (int) data_get($order->information, 'version', 0)
            || $quote->quoted_amount === null
            || Money::toMinor((string) $quote->quoted_amount, (string) $quote->quote_currency)
                !== Money::toMinor((string) $order->grand_total, (string) $order->currency)
            || strtoupper((string) $quote->quote_currency) !== strtoupper((string) $order->currency)) {
            throw new PaymentGatewayException('Accepted quote and order payment terms do not match.');
        }

        $details = (array) $quote->quotation_details;
        $terms = $details['payment_installments'] ?? null;
        if ($terms === null) {
            // When the quotation explicitly mentions a deposit, never silently
            // charge the full balance through an unapproved installment flow.
            if (preg_match('/\b(deposit|advance payment|installment|instalment)\b/i', (string) ($details['payment_terms'] ?? ''))) {
                throw new PaymentGatewayException('Quotation mentions installments without approved structured amounts. Finance review required.');
            }
            return null;
        }
        if (! is_array($terms) || count($terms) !== 2 || array_keys($terms) !== [0, 1]) {
            throw new PaymentGatewayException('Accepted quote installment schedule must contain a deposit and final balance.');
        }
        $currency = strtoupper((string) $order->currency);
        $total = Money::toMinor((string) $order->grand_total, $currency);
        $amounts = [];
        foreach ($terms as $index => $term) {
            if (! is_array($term) || ($term['kind'] ?? null) !== ($index === 0 ? 'deposit' : 'balance')
                || ! is_int($term['amount_minor'] ?? null) || $term['amount_minor'] < 1
                || ($term['amount_minor'] > $total)) {
                throw new PaymentGatewayException('Accepted installment has invalid kind or amount.');
            }
            $amounts[] = $term['amount_minor'];
        }
        if (array_sum($amounts) !== $total) {
            throw new PaymentGatewayException('Accepted installments must exactly equal the order total.');
        }
        if ($order->payments()->exists()) {
            throw new PaymentGatewayException('Installment terms cannot be added after a payment attempt exists.');
        }

        // Called under the order lock by PaymentOrchestrator. This transaction
        // also permits safe use from another trusted server-side conversion path.
        return DB::transaction(function () use ($order, $quote, $amounts, $total, $currency, $terms): OrderInstallmentSchedule {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $already = $locked->installmentSchedule()->first();
            if ($already) {
                return $already;
            }
            if ($locked->payments()->exists()) {
                throw new PaymentGatewayException('Payment attempts exist; cannot establish an installment contract.');
            }
            $schedule = OrderInstallmentSchedule::query()->create([
                'order_id' => $locked->id,
                'bulk_quote_request_id' => $quote->id,
                'currency' => $currency,
                'total_minor' => $total,
                'accepted_terms_hash' => hash('sha256', json_encode([
                    'quote' => $quote->id, 'version' => $quote->quotation_version,
                    'total' => $total, 'currency' => $currency, 'terms' => $terms,
                ], JSON_THROW_ON_ERROR)),
                'accepted_at' => $quote->updated_at ?? now(),
            ]);
            foreach ($amounts as $index => $minor) {
                OrderPaymentInstallment::query()->create([
                    'order_installment_schedule_id' => $schedule->id,
                    'order_id' => $locked->id,
                    'sequence' => $index + 1,
                    'kind' => $index === 0 ? 'deposit' : 'balance',
                    'amount_minor' => $minor,
                    'currency' => $currency,
                    'due_at' => null, // No speculative due date inferred from text.
                ]);
            }
            return $schedule;
        }, 3);
    }

    public function nextDue(Order $order): ?OrderPaymentInstallment
    {
        $schedule = $order->installmentSchedule()->with('installments')->first();
        if (! $schedule) {
            return null;
        }
        if ($schedule->currency !== strtoupper((string) $order->currency)
            || $schedule->total_minor !== Money::toMinor((string) $order->grand_total, (string) $order->currency)) {
            throw new PaymentGatewayException('The approved installment schedule differs from the current order total.');
        }
        $receipts = $order->payments()->where('status', 'paid')->get();
        $paidByInstallment = [];
        foreach ($receipts as $receipt) {
            if (! $receipt->order_payment_installment_id) {
                throw new PaymentGatewayException('Unallocated receipt requires finance review before further installments.');
            }
            $id = (int) $receipt->order_payment_installment_id;
            if (isset($paidByInstallment[$id])) {
                throw new PaymentGatewayException('Multiple captures found for an approved installment.');
            }
            $paidByInstallment[$id] = Money::toMinor((string) $receipt->amount, (string) $receipt->currency);
        }
        foreach ($schedule->installments as $installment) {
            $paid = $paidByInstallment[$installment->id] ?? null;
            if ($paid !== null && $paid !== (int) $installment->amount_minor) {
                throw new PaymentGatewayException('Installment receipt amount mismatch.');
            }
            if ($paid === null) {
                return $installment;
            }
            unset($paidByInstallment[$installment->id]);
        }
        if ($paidByInstallment !== []) {
            throw new PaymentGatewayException('Receipt is assigned outside the approved installment schedule.');
        }
        return null;
    }

    public function isFullyPaid(Order $order): bool
    {
        $schedule = $order->installmentSchedule()->first();
        return ! $schedule || $this->nextDue($order) === null;
    }
}
