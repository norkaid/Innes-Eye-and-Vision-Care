<?php

namespace App\Services;

use App\Models\Service;

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

    /**
     * The fee and the online deposit for an appointment for this service.
     *
     * @return array{fee: float, percent: int, deposit: float, balance: float}
     */
    public function appointmentCharges(?Service $service): array
    {
        $fee = $service && (float) $service->booking_fee > 0
            ? (float) $service->booking_fee
            : max(0, (float) $this->settings->get('consultation_fee', 0));
        $percent = min(100, max(0, $this->settings->int('deposit_percent')));
        $deposit = round($fee * $percent / 100, 2);

        return ['fee' => round($fee, 2), 'percent' => $percent, 'deposit' => $deposit, 'balance' => round($fee - $deposit, 2)];
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
