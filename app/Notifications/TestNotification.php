<?php

namespace App\Notifications;

use App\Services\Settings;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TestNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Test email from '.app(Settings::class)->get('business_name'))
            ->line('This is a test. Customer email notifications are working.');
    }
}
