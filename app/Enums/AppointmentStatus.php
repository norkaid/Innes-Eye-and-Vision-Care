<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case RescheduleProposed = 'reschedule_proposed';
    case RescheduleRequested = 'reschedule_requested';
    case Confirmed = 'confirmed';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending review',
            self::RescheduleProposed => 'New time proposed',
            self::RescheduleRequested => 'Reschedule requested',
            self::Confirmed => 'Confirmed',
            self::Declined => 'Declined',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
            self::NoShow => 'Did not attend',
        };
    }

    /** Visual tone for the status badge. Text label is always shown too, so colour is never the only signal. */
    public function tone(): string
    {
        return match ($this) {
            self::Pending, self::RescheduleRequested, self::RescheduleProposed => 'amber',
            self::Confirmed => 'teal',
            self::Completed => 'navy',
            self::Declined, self::Cancelled, self::NoShow => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending, self::RescheduleRequested, self::RescheduleProposed => 'clock',
            self::Confirmed, self::Completed => 'check',
            self::Declined, self::Cancelled, self::NoShow => 'x',
        };
    }

    /** States from which a request is still "open" (needs staff or customer action). */
    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::RescheduleProposed, self::RescheduleRequested, self::Confirmed], true);
    }

    /**
     * Status transitions that staff are allowed to perform.
     *
     * @return list<self>
     */
    public function staffTransitions(): array
    {
        return match ($this) {
            self::Pending, self::RescheduleRequested => [self::Confirmed, self::RescheduleProposed, self::Declined, self::Cancelled],
            self::RescheduleProposed => [self::Confirmed, self::RescheduleProposed, self::Declined, self::Cancelled],
            self::Confirmed => [self::Completed, self::NoShow, self::RescheduleProposed, self::Cancelled],
            self::Declined, self::Cancelled, self::Completed, self::NoShow => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->staffTransitions(), true);
    }

    /** Customers may only ask to cancel or reschedule an open request. */
    public function customerCanCancel(): bool
    {
        return $this->isOpen();
    }

    public function customerCanRequestReschedule(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed, self::RescheduleProposed], true);
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
