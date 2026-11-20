<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Payment;
use App\Notifications\AppointmentPaymentReceived;
use App\Notifications\AppointmentRequestReceived;
use App\Notifications\AppointmentStatusChanged;

/**
 * Tells customers about their appointment by email and/or SMS, as they chose on the booking form.
 * Texts are short and never contain medical details.
 *
 * @return array{email: bool, sms: bool} whether each channel was really delivered
 */
class AppointmentNotifier
{
    public function __construct(private NotificationService $notifications, private Settings $settings) {}

    /** @return array{email: bool, sms: bool} */
    public function requestReceived(Appointment $a): array
    {
        return [
            'email' => $a->notify_email && $this->notifications->toCustomer($a->customer_email, new AppointmentRequestReceived($a), $a),
            // When a deposit is due, the text goes out once it is paid instead.
            'sms' => $a->notify_sms && ! $a->canBePaid()
                && $this->notifications->smsToCustomer($a->customer_phone, 'AppointmentRequestReceivedSms',
                    "{$this->name()}: we received your appointment request {$a->reference_code}. It is not yet confirmed; we will contact you with a time.", $a),
        ];
    }

    /** @return array{email: bool, sms: bool} */
    public function depositPaid(Appointment $a, Payment $payment): array
    {
        return [
            'email' => $a->notify_email && $this->notifications->toCustomer($a->customer_email, new AppointmentPaymentReceived($a, $payment), $a),
            'sms' => $a->notify_sms && $this->notifications->smsToCustomer($a->customer_phone, 'AppointmentDepositPaidSms',
                "{$this->name()}: deposit of ".ShopService::money($payment->amount())." received for request {$a->reference_code}. We will confirm your appointment time shortly.", $a),
        ];
    }

    /** @return array{email: bool, sms: bool} */
    public function statusChanged(Appointment $a): array
    {
        $a->loadMissing('branch');
        $text = $this->statusText($a);

        return [
            'email' => $a->notify_email && $this->notifications->toCustomer($a->customer_email, new AppointmentStatusChanged($a), $a),
            'sms' => $a->notify_sms && $text !== null && $this->notifications->smsToCustomer($a->customer_phone, 'AppointmentStatusSms', $text, $a),
        ];
    }

    private function statusText(Appointment $a): ?string
    {
        $name = $this->name();
        $branch = $a->branch?->name;

        return match ($a->status) {
            AppointmentStatus::Confirmed => "{$name}: your appointment {$a->reference_code} is CONFIRMED for ".$a->confirmed_start_at->format('D j M Y, H:i')." at {$branch}."
                .($a->balanceDue() ? ' Balance to pay at the clinic: '.ShopService::money($a->balanceDue()).'.' : ''),
            AppointmentStatus::RescheduleProposed => "{$name}: for request {$a->reference_code} we propose ".$a->proposed_start_at->format('D j M Y, H:i')." at {$branch}. Not yet confirmed – please call us to accept.",
            AppointmentStatus::Declined => "{$name}: sorry, we could not accept appointment request {$a->reference_code}. Please contact us or book another time.",
            AppointmentStatus::Cancelled => "{$name}: appointment {$a->reference_code} has been cancelled. Please contact us if you have questions.",
            default => null,
        };
    }

    private function name(): string
    {
        return (string) $this->settings->get('business_name');
    }
}
