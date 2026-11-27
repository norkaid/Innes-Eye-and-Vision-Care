<?php

namespace App\Models;

use App\Enums\ProductAvailability;
use App\Models\Concerns\HasSlug;
use App\Services\PaystackClient;
use App\Services\ShopService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasSlug, SoftDeletes;

    protected $fillable = ['product_category_id', 'name', 'slug', 'brand', 'short_description', 'description', 'image_path', 'price', 'availability_status', 'is_published', 'is_purchasable', 'stock_quantity'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_purchasable' => 'boolean',
            'stock_quantity' => 'integer',
            'price' => 'decimal:2',
            'availability_status' => ProductAvailability::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    /** Published products in an active category. */
    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true)
            ->whereHas('category', fn ($c) => $c->where('is_active', true));
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }
        $like = '%'.$term.'%';

        return $q->where(fn ($w) => $w->whereLike('name', $like)
            ->orWhereLike('brand', $like)
            ->orWhereLike('short_description', $like));
    }

    /** Can be added to the cart: shop on, sold online, priced, published and in stock. */
    public function canBeBought(): bool
    {
        return app(ShopService::class)->enabled()
            && $this->is_purchasable && $this->is_published && ! $this->trashed()
            && (float) $this->price > 0
            && $this->availability_status !== ProductAvailability::Unavailable
            && ($this->stock_quantity === null || $this->stock_quantity > 0);
    }

    /**
     * Plain-language reasons why this product cannot be bought online right now (empty = it can).
     *
     * @return list<string>
     */
    public function purchaseBlockers(): array
    {
        $reasons = [];
        if (! config('innes.features.shop')) {
            $reasons[] = 'The shop is switched off (FEATURE_SHOP=false in .env).';
        }
        if (! app(PaystackClient::class)->configured()) {
            $reasons[] = 'Paystack is not set up: add PAYSTACK_SECRET_KEY to .env, then run php artisan config:clear and restart the site.';
        }
        if (! $this->is_purchasable) {
            $reasons[] = '"Sell online" is not ticked.';
        }
        if (! $this->is_published) {
            $reasons[] = 'The product is not published.';
        }
        if (! ((float) $this->price > 0)) {
            $reasons[] = 'It has no price.';
        }
        if ($this->availability_status === ProductAvailability::Unavailable) {
            $reasons[] = 'Its availability label is "Unavailable".';
        }
        if ($this->stock_quantity !== null && $this->stock_quantity <= 0) {
            $reasons[] = 'Stock available online is 0.';
        }

        return $reasons;
    }

    /** Largest quantity one customer can put in the cart. */
    public function maxOrderQuantity(): int
    {
        return min(10, $this->stock_quantity ?? 10);
    }
}
