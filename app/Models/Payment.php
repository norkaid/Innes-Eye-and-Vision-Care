<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A Paystack transaction attempt. Status and amounts are written only by
 * PaymentService after the server has verified the transaction with Paystack.
 */
class Payment extends Model
{
    public const PENDING = 'pending';

    public const SUCCESS = 'success';

    public const FAILED = 'failed';

    public const ABANDONED = 'abandoned';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'paid_at' => 'datetime'];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function amount(): float
    {
        return $this->amount_minor / 100;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::SUCCESS => 'Successful',
            self::FAILED => 'Failed',
            self::ABANDONED => 'Not completed',
            default => 'Started',
        };
    }

    public function channelLabel(): ?string
    {
        return $this->channel ? ucwords(str_replace('_', ' ', $this->channel)) : null;
    }
}
