<?php

namespace App\Models;

use App\Contracts\Payable;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * A shop order. Prices and totals are copied from the database when the order is
 * placed; status and payment fields are never mass-assignable.
 */
class Order extends Model implements Payable
{
    public const REFERENCE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    protected $fillable = ['customer_name', 'customer_email', 'customer_phone', 'fulfilment', 'pickup_branch_id', 'delivery_address', 'customer_note'];

    protected $hidden = ['staff_note'];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'stock_issue' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public static function newReference(): string
    {
        do {
            $ref = 'ORD-';
            for ($i = 0; $i < 8; $i++) {
                $ref .= self::REFERENCE_ALPHABET[random_int(0, strlen(self::REFERENCE_ALPHABET) - 1)];
            }
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable')->latest();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pickupBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'pickup_branch_id')->withTrashed();
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }
        $like = '%'.$term.'%';

        return $q->where(fn ($w) => $w->where('reference', 'like', $like)
            ->orWhere('customer_name', 'like', $like)
            ->orWhere('customer_email', 'like', $like)
            ->orWhere('customer_phone', 'like', $like));
    }

    /** Link to the order page that works without signing in (signed, cannot be guessed). */
    public function publicUrl(): string
    {
        return URL::signedRoute('shop.orders.show', $this);
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === OrderStatus::PendingPayment && $this->payment_status === PaymentStatus::Unpaid;
    }

    // Payable

    public function amountDueMinor(): int
    {
        return $this->isAwaitingPayment() ? (int) round((float) $this->total * 100) : 0;
    }

    public function paymentEmail(): string
    {
        return $this->customer_email;
    }

    public function paymentDescription(): string
    {
        return 'Order '.$this->reference;
    }

    public function markPaid(Payment $payment): void
    {
        $this->payment_status = PaymentStatus::Paid;
        $this->status = OrderStatus::Paid;
        $this->paid_at = $payment->paid_at ?? now();

        // Take the items out of stock. Never below zero: a shortfall is flagged for staff.
        foreach ($this->items()->with('product')->get() as $item) {
            $product = $item->product_id ? Product::withTrashed()->lockForUpdate()->find($item->product_id) : null;
            if ($product && $product->stock_quantity !== null) {
                if ($product->stock_quantity < $item->quantity) {
                    $this->stock_issue = true;
                }
                $product->stock_quantity = max(0, $product->stock_quantity - $item->quantity);
                $product->save();
            }
        }
        $this->save();
    }

    public function paymentReturnUrl(): string
    {
        return $this->publicUrl();
    }

    public function fulfilmentLabel(): string
    {
        return $this->fulfilment === 'delivery' ? 'Delivery' : 'Pickup at '.($this->pickupBranch?->name ?? 'branch');
    }

    public function shortSummary(): string
    {
        return Str::limit($this->items->map(fn ($i) => $i->quantity.' × '.$i->product_name)->join(', '), 120);
    }
}
