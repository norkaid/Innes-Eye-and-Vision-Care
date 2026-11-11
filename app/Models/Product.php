<?php

namespace App\Models;

use App\Enums\ProductAvailability;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasSlug, SoftDeletes;

    protected $fillable = ['product_category_id', 'name', 'slug', 'brand', 'short_description', 'description', 'image_path', 'price', 'availability_status', 'is_published'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
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

        return $q->where(fn ($w) => $w->where('name', 'like', $like)
            ->orWhere('brand', 'like', $like)
            ->orWhere('short_description', 'like', $like));
    }
}
