<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Services\Settings;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Minimal acknowledgement – no medical details, no customer note. */
class AppointmentRequestReceived extends Notification
{
    public function __construct(public Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = app(Settings::class)->get('business_name');

        return (new MailMessage)
            ->subject("We received your appointment request ({$this->appointment->reference_code})")
            ->greeting('Thank you for your request')
            ->line("{$name} has received your appointment request. Your reference is **{$this->appointment->reference_code}**.")
            ->line('This is a request only – it is **not yet confirmed**. A member of our team will review it and contact you to confirm a time.')
            ->line('Please do not reply with medical information by email.');
    }
}
