<?php

namespace App\Notifications;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentStatusChanged extends Notification
{
    public function __construct(public Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->appointment->loadMissing('branch');
        $ref = $a->reference_code;
        $mail = (new MailMessage)->subject("Update on your appointment request ({$ref})");

        return match ($a->status) {
            AppointmentStatus::Confirmed => $mail->greeting('Your appointment is confirmed')
                ->line('Date and time: **'.$a->confirmed_start_at->format('l j F Y, H:i').'**')
                ->line("Branch: {$a->branch->name}")
                ->line('If you need to change this, please contact the branch.'),
            AppointmentStatus::RescheduleProposed => $mail->greeting('We have proposed a new time')
                ->line('Proposed date and time: **'.$a->proposed_start_at->format('l j F Y, H:i').'**')
                ->line('This time is not yet confirmed. Please contact the branch to accept it or arrange another time.'),
            AppointmentStatus::Declined => $mail->greeting('We could not accept your request')
                ->line('Unfortunately we could not accept this appointment request. Please contact the branch or submit a new request.'),
            default => $mail->greeting('Your appointment has been cancelled')
                ->line('This appointment request has been cancelled. Please contact the branch if you have any questions.'),
        };
    }
}
