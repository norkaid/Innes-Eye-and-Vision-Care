<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Processing = 'processing';
    case ReadyForPickup = 'ready_for_pickup';
    case OutForDelivery = 'out_for_delivery';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Awaiting payment',
            self::Paid => 'Paid – to be prepared',
            self::Processing => 'Being prepared',
            self::ReadyForPickup => 'Ready for pickup',
            self::OutForDelivery => 'Out for delivery',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::PendingPayment => 'gray',
            self::Paid, self::Processing => 'amber',
            self::ReadyForPickup, self::OutForDelivery => 'teal',
            self::Completed => 'navy',
            self::Cancelled => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PendingPayment, self::Paid, self::Processing => 'clock',
            self::ReadyForPickup, self::OutForDelivery, self::Completed => 'check',
            self::Cancelled => 'x',
        };
    }

    /**
     * Changes staff may make. "Paid" is only ever set by a verified payment.
     *
     * @return list<self>
     */
    public function staffTransitions(string $fulfilment): array
    {
        $handOver = $fulfilment === 'delivery' ? self::OutForDelivery : self::ReadyForPickup;

        return match ($this) {
            self::PendingPayment => [self::Cancelled],
            self::Paid => [self::Processing, $handOver, self::Cancelled],
            self::Processing => [$handOver, self::Cancelled],
            self::ReadyForPickup, self::OutForDelivery => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
