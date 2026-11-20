<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\Payment;
use App\Services\ShopService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Receipt for an appointment booking fee. The request itself still needs staff confirmation. */
class AppointmentPaymentReceived extends Notification
{
    public function __construct(public Appointment $appointment, public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Booking fee received ({$this->appointment->reference_code})")
            ->line('We have received your payment of **'.ShopService::money($this->payment->amount())."** for appointment request **{$this->appointment->reference_code}**.")
            ->line('Your appointment is **not yet confirmed**. Our team will contact you to confirm a time.')
            ->line("Payment reference: {$this->payment->reference}");
    }
}
