<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['old_status', 'new_status', 'changed_by', 'internal_note'];

    protected function casts(): array
    {
        return [
            'old_status' => AppointmentStatus::class,
            'new_status' => AppointmentStatus::class,
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
