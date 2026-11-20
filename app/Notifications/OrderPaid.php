<?php

namespace App\Notifications;

use App\Models\Order;
use App\Services\Settings;
use App\Services\ShopService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Order confirmation and receipt, sent after Paystack has confirmed payment. */
class OrderPaid extends Notification
{
    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing('items', 'pickupBranch');
        $mail = (new MailMessage)
            ->subject("Order {$order->reference} – payment received")
            ->greeting('Thank you for your order')
            ->line(app(Settings::class)->get('business_name').' has received your payment of **'.ShopService::money($order->total)."** for order **{$order->reference}**.");

        foreach ($order->items as $item) {
            $mail->line("{$item->quantity} × {$item->product_name} – ".ShopService::money($item->line_total));
        }
        if ((float) $order->delivery_fee > 0) {
            $mail->line('Delivery – '.ShopService::money($order->delivery_fee));
        }

        return $mail
            ->line($order->fulfilment === 'delivery'
                ? 'We will contact you to arrange delivery.'
                : 'We will let you know when your order is ready to collect at '.($order->pickupBranch?->name ?? 'the branch').'.')
            ->action('View your order', $order->publicUrl());
    }
}
