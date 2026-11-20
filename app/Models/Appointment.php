<?php

namespace App\Models;

use App\Contracts\Payable;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class Appointment extends Model implements Payable
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
            'payment_status' => PaymentStatus::class,
            'amount_due' => 'decimal:2',
            'paid_at' => 'datetime',
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

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable')->latest();
    }

    /** Payment is possible while the fee is unpaid and the request is still open. */
    public function canBePaid(): bool
    {
        return $this->payment_status === PaymentStatus::Unpaid && $this->status->isOpen() && (float) $this->amount_due > 0;
    }

    /** Link to the payment page that works without signing in (signed, cannot be guessed). */
    public function paymentUrl(): string
    {
        return URL::signedRoute('appointments.pay', $this);
    }

    // Payable

    public function amountDueMinor(): int
    {
        return $this->canBePaid() ? (int) round((float) $this->amount_due * 100) : 0;
    }

    public function paymentEmail(): string
    {
        return $this->customer_email;
    }

    public function paymentDescription(): string
    {
        return 'Appointment fee '.$this->reference_code;
    }

    public function markPaid(Payment $payment): void
    {
        $this->payment_status = PaymentStatus::Paid;
        $this->paid_at = $payment->paid_at ?? now();
        $this->save();
    }

    public function paymentReturnUrl(): string
    {
        return $this->paymentUrl();
    }
}
