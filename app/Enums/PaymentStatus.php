<?php

namespace App\Enums;

/** Payment state of an order or an appointment request. */
enum PaymentStatus: string
{
    case NotRequired = 'not_required';
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::NotRequired => 'No payment needed',
            self::Unpaid => 'Not paid',
            self::Paid => 'Paid',
            self::Refunded => 'Refunded',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Paid => 'teal',
            self::Unpaid => 'amber',
            self::Refunded, self::NotRequired => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Paid => 'check',
            self::Unpaid => 'clock',
            self::Refunded, self::NotRequired => 'x',
        };
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
