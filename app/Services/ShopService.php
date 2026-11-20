<?php

namespace App\Services;

/** Shop on/off and the fulfilment options set in Admin → Settings. */
class ShopService
{
    public function __construct(private Settings $settings, private PaystackClient $paystack) {}

    /** The shop is shown only when switched on and Paystack is set up. */
    public function enabled(): bool
    {
        return (bool) config('innes.features.shop') && $this->paystack->configured();
    }

    public function appointmentPaymentsEnabled(): bool
    {
        return (bool) config('innes.features.appointment_payments') && $this->paystack->configured();
    }

    public function pickupEnabled(): bool
    {
        return $this->settings->bool('shop_pickup_enabled');
    }

    public function deliveryEnabled(): bool
    {
        return $this->settings->bool('shop_delivery_enabled');
    }

    public function deliveryFee(): float
    {
        return round(max(0, (float) $this->settings->get('shop_delivery_fee', 0)), 2);
    }

    /** @return array<string,string> */
    public function fulfilmentOptions(): array
    {
        return array_filter([
            'pickup' => $this->pickupEnabled() ? 'Collect from a branch (free)' : null,
            'delivery' => $this->deliveryEnabled() ? 'Delivery'.($this->deliveryFee() > 0 ? ' (GH₵ '.number_format($this->deliveryFee(), 2).')' : '') : null,
        ]);
    }

    public static function money(float|string|null $amount): string
    {
        return 'GH₵ '.number_format((float) $amount, 2);
    }
}
