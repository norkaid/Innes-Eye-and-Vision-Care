<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Appointment extends Model
{
    use HasFactory;

    /**
     * Status, staff notes and handling fields are deliberately NOT fillable so that
     * public input can never set them. They are written only by AppointmentService.
     */
    protected $fillable = [
        'branch_id', 'service_id', 'customer_name', 'customer_email', 'customer_phone',
        'requested_date', 'requested_time', 'customer_note',
    ];

    protected $hidden = ['staff_note'];

    protected function casts(): array
    {
        return [
            'requested_date' => 'date',
            'proposed_start_at' => 'datetime',
            'confirmed_start_at' => 'datetime',
            'confirmed_end_at' => 'datetime',
            'status_updated_at' => 'datetime',
            'status' => AppointmentStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference_code';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(AppointmentStatusHistory::class)->latest('id');
    }

    /** Preferred start as a Carbon instance (date + time, or start of day if no time). */
    public function requestedStart(): Carbon
    {
        $date = $this->requested_date->format('Y-m-d');

        return Carbon::parse($date.' '.($this->requested_time ?? '00:00:00'));
    }

    /** The relevant start time for cut-off rules: confirmed time, else the requested time. */
    public function effectiveStart(): Carbon
    {
        return $this->confirmed_start_at ?? $this->requestedStart();
    }

    public function scopeStatus(Builder $q, ?string $status): Builder
    {
        return $status ? $q->where('status', $status) : $q;
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }
        $like = '%'.$term.'%';

        return $q->where(fn ($w) => $w->where('reference_code', 'like', $like)
            ->orWhere('customer_name', 'like', $like)
            ->orWhere('customer_email', 'like', $like)
            ->orWhere('customer_phone', 'like', $like));
    }
}
