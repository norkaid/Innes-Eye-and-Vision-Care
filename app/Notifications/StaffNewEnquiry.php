<?php

namespace App\Notifications;

use App\Models\Enquiry;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffNewEnquiry extends Notification
{
    public function __construct(public Enquiry $enquiry) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New website enquiry')
            ->line('A new enquiry was submitted through the website.')
            ->action('Review in dashboard', route('admin.enquiries.show', $this->enquiry));
    }
}
