<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Staff alert – contains the reference only; staff sign in to see details. */
class StaffNewAppointmentRequest extends Notification
{
    public function __construct(public Appointment $appointment, public bool $reschedule = false) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $what = $this->reschedule ? 'A customer has requested to reschedule' : 'A new appointment request was submitted';

        return (new MailMessage)
            ->subject(($this->reschedule ? 'Reschedule request ' : 'New appointment request ').$this->appointment->reference_code)
            ->line("{$what} (reference {$this->appointment->reference_code}).")
            ->action('Review in dashboard', route('admin.appointments.show', $this->appointment));
    }
}
