<?php

namespace App\Enums;

enum EnquiryStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::InProgress => 'In progress',
            self::Resolved => 'Resolved',
            self::Spam => 'Spam',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'amber',
            self::InProgress => 'teal',
            self::Resolved => 'navy',
            self::Spam => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::New => 'clock',
            self::InProgress => 'clock',
            self::Resolved => 'check',
            self::Spam => 'x',
        };
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
