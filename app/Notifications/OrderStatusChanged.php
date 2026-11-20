<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Settings;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification
{
    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $o = $this->order->loadMissing('pickupBranch');

        return (new MailMessage)
            ->subject("Order {$o->reference}: ".$o->status->label())
            ->line(OrderStatusChanged::text($o, app(Settings::class)->get('business_name')))
            ->action('View your order', $o->publicUrl());
    }

    /** Short message used for email, SMS and the account page. */
    public static function text(Order $o, string $business): ?string
    {
        return match ($o->status) {
            OrderStatus::ReadyForPickup => "{$business}: your order {$o->reference} is ready to collect at ".($o->pickupBranch?->name ?? 'the branch').'.',
            OrderStatus::OutForDelivery => "{$business}: your order {$o->reference} is on its way to you.",
            OrderStatus::Completed => "{$business}: your order {$o->reference} is complete. Thank you!",
            OrderStatus::Cancelled => "{$business}: your order {$o->reference} has been cancelled. Please contact us if you have questions.",
            default => null,
        };
    }
}
