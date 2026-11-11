<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, HasSlug, SoftDeletes;

    public const DAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    protected $fillable = ['name', 'slug', 'address', 'city', 'region', 'phone', 'email', 'map_url', 'description', 'is_active', 'display_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'display_order' => 'integer'];
    }

    public function openingHours(): HasMany
    {
        return $this->hasMany(BranchOpeningHour::class)->orderBy('day_of_week');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->withTimestamps();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('display_order')->orderBy('name');
    }

    public function hoursFor(int $isoDay): ?BranchOpeningHour
    {
        return $this->openingHours->firstWhere('day_of_week', $isoDay);
    }

    public function directionsUrl(): ?string
    {
        if ($this->map_url) {
            return $this->map_url;
        }
        if ($this->address && ! str_contains(strtoupper($this->address), 'DEMO')) {
            return 'https://www.google.com/maps/search/?api=1&query='.urlencode($this->address.', '.$this->city.', Ghana');
        }

        return null;
    }
}
