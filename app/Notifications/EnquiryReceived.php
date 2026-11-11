<?php

namespace App\Notifications;

use App\Models\Enquiry;
use App\Services\Settings;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnquiryReceived extends Notification
{
    public function __construct(public Enquiry $enquiry) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = app(Settings::class)->get('business_name');

        return (new MailMessage)
            ->subject('We received your message')
            ->line("Thank you for contacting {$name}. We have received your enquiry and a member of our team will respond.");
    }
}
