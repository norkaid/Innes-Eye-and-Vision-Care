<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Staff alert for a newly paid order – reference only; details are in the dashboard. */
class StaffNewOrder extends Notification
{
    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New paid order {$this->order->reference}")
            ->line("A new order has been paid online (reference {$this->order->reference}).")
            ->action('Open in dashboard', route('admin.orders.show', $this->order));
    }
}
