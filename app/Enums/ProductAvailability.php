<?php

namespace App\Enums;

/**
 * Availability labels for catalogue items. These are labels only – the system does not track stock.
 */
enum ProductAvailability: string
{
    case Available = 'available';
    case Enquire = 'enquire';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available in branch',
            self::Enquire => 'Enquire for availability',
            self::Unavailable => 'Currently unavailable',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Available => 'teal',
            self::Enquire => 'navy',
            self::Unavailable => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Available => 'check',
            self::Enquire => 'info',
            self::Unavailable => 'x',
        };
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
