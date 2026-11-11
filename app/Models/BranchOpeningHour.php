<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchOpeningHour extends Model
{
    protected $fillable = ['day_of_week', 'opens_at', 'closes_at', 'is_closed'];

    protected function casts(): array
    {
        return ['is_closed' => 'boolean', 'day_of_week' => 'integer'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isOpen(): bool
    {
        return ! $this->is_closed && $this->opens_at && $this->closes_at;
    }

    public function dayName(): string
    {
        return Branch::DAYS[$this->day_of_week] ?? '';
    }

    public function display(): string
    {
        if (! $this->isOpen()) {
            return 'Closed';
        }

        return substr($this->opens_at, 0, 5).' – '.substr($this->closes_at, 0, 5);
    }
}
