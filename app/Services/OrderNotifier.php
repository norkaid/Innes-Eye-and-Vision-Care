<?php

namespace App\Services;

use App\Models\Order;
use App\Notifications\AccountMessage;
use App\Notifications\OrderStatusChanged;

/** Tells shop customers about their order by email, SMS and (if they have an account) in their account. */
class OrderNotifier
{
    public function __construct(private NotificationService $notifications, private Settings $settings) {}

    /** Guests get both; account holders get the channels they chose in their profile. */
    public function wantsEmail(Order $order): bool
    {
        return ! $order->user || $order->user->notify_email;
    }

    public function wantsSms(Order $order): bool
    {
        return ! $order->user || $order->user->notify_sms;
    }

    public function paid(Order $order): void
    {
        $this->wantsSms($order) && $this->notifications->smsToCustomer($order->customer_phone, 'OrderPaidSms',
            "{$this->name()}: payment of ".ShopService::money($order->total)." received for order {$order->reference}. We will let you know when it is ready.", $order);
        $this->toAccount($order, 'Order paid', 'We received your payment of '.ShopService::money($order->total)." for order {$order->reference}.");
    }

    public function statusChanged(Order $order): void
    {
        $text = OrderStatusChanged::text($order->loadMissing('pickupBranch'), $this->name());
        if ($text === null) {
            return;
        }
        $this->wantsEmail($order) && $this->notifications->toCustomer($order->customer_email, new OrderStatusChanged($order), $order);
        $this->wantsSms($order) && $this->notifications->smsToCustomer($order->customer_phone, 'OrderStatusSms', $text, $order);
        $this->toAccount($order, 'Order '.strtolower($order->status->label()), $text);
    }

    private function toAccount(Order $order, string $title, string $body): void
    {
        $user = $order->user;
        if ($user && $user->isCustomer()) {
            $user->notify(new AccountMessage($title, $body, $order->publicUrl()));
        }
    }

    private function name(): string
    {
        return (string) $this->settings->get('business_name');
    }
}
