<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, HasSlug, SoftDeletes;

    protected $fillable = ['name', 'slug', 'short_description', 'description', 'preparation', 'image_path', 'is_published', 'display_order'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'display_order' => 'integer'];
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('display_order')->orderBy('name');
    }
}
