<?php

namespace App\Services;

use App\Contracts\Payable;
use App\Exceptions\PaymentGatewayException;
use App\Models\Appointment;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\OrderPaid;
use App\Notifications\StaffNewOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Starts and confirms Paystack payments for orders and appointment requests.
 *
 * Rules: the amount always comes from the database (never from the browser); a payment
 * only counts after the server has verified it with Paystack and the amount and currency
 * match; confirming is idempotent (the redirect and the webhook may both arrive).
 */
class PaymentService
{
    public function __construct(
        private PaystackClient $paystack,
        private NotificationService $notifications,
        private AuditLogger $audit,
    ) {}

    public function available(): bool
    {
        return $this->paystack->configured();
    }

    /**
     * Creates a payment attempt and returns the Paystack checkout URL to redirect to.
     *
     * @param  Payable&Model  $payable
     */
    public function start(Payable $payable): string
    {
        $amount = $payable->amountDueMinor();
        if ($amount <= 0) {
            throw new PaymentGatewayException('There is nothing to pay.');
        }

        $payment = new Payment;
        $payment->forceFill([
            'reference' => 'IEV'.now()->format('ymd').'-'.Str::upper(Str::random(12)),
            'payable_type' => $payable->getMorphClass(),
            'payable_id' => $payable->getKey(),
            'amount_minor' => $amount,
            'currency' => config('services.paystack.currency', 'GHS'),
            'status' => Payment::PENDING,
            'customer_email' => $payable->paymentEmail(),
        ])->save();

        try {
            $checkout = $this->paystack->initialize(
                $payable->paymentEmail(),
                $amount,
                $payment->reference,
                route('payments.callback'),
                ['description' => $payable->paymentDescription(), 'payment_id' => $payment->id],
            );
        } catch (PaymentGatewayException $e) {
            $payment->forceFill(['status' => Payment::FAILED, 'gateway_response' => mb_substr($e->getMessage(), 0, 250)])->save();

            throw $e;
        }

        return $checkout['authorization_url'];
    }

    /**
     * Verifies a payment with Paystack and records the outcome. Safe to call repeatedly.
     */
    public function confirm(string $reference): ?Payment
    {
        $payment = Payment::where('reference', $reference)->first();
        if (! $payment) {
            return null;
        }
        if ($payment->status === Payment::SUCCESS) {
            return $payment;
        }

        $data = $this->paystack->verify($reference);

        $justPaid = DB::transaction(function () use ($payment, $data) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();
            if ($payment->status === Payment::SUCCESS) {
                return false;
            }

            $status = (string) ($data['status'] ?? '');
            $matches = (int) ($data['amount'] ?? -1) === $payment->amount_minor
                && strtoupper((string) ($data['currency'] ?? '')) === strtoupper($payment->currency)
                && (string) ($data['reference'] ?? '') === $payment->reference;

            $payment->forceFill([
                'channel' => isset($data['channel']) ? mb_substr((string) $data['channel'], 0, 30) : $payment->channel,
                'gateway_transaction_id' => isset($data['id']) ? (string) $data['id'] : $payment->gateway_transaction_id,
                'gateway_response' => isset($data['gateway_response']) ? mb_substr((string) $data['gateway_response'], 0, 250) : $payment->gateway_response,
            ]);

            if ($status === 'success' && $matches) {
                $payment->status = Payment::SUCCESS;
                $payment->paid_at = now();
                $payment->save();

                $payable = $payment->payable()->lockForUpdate()->first();
                $payable?->markPaid($payment);
                $this->audit->log('payment.succeeded', $payable, ['reference' => $payment->reference, 'amount_minor' => $payment->amount_minor]);

                return true;
            }

            if ($status === 'success' && ! $matches) {
                $payment->status = Payment::FAILED;
                $payment->gateway_response = 'Amount or currency did not match the order – check in the Paystack dashboard';
                $this->audit->log('payment.mismatch', null, ['reference' => $payment->reference]);
            } elseif (in_array($status, ['failed', 'reversed'], true)) {
                $payment->status = Payment::FAILED;
            } elseif ($status === 'abandoned') {
                $payment->status = Payment::ABANDONED;
            }
            $payment->save();

            return false;
        });

        $payment->refresh();
        if ($justPaid) {
            $this->notifyPaid($payment);
        }

        return $payment;
    }

    private function notifyPaid(Payment $payment): void
    {
        $payable = $payment->payable;
        if ($payable instanceof Order) {
            app(OrderNotifier::class)->wantsEmail($payable) && $this->notifications->toCustomer($payable->customer_email, new OrderPaid($payable), $payable);
            app(OrderNotifier::class)->paid($payable);
            $this->notifications->toStaff(new StaffNewOrder($payable), $payable);
        } elseif ($payable instanceof Appointment) {
            app(AppointmentNotifier::class)->depositPaid($payable, $payment);
        }
    }
}
