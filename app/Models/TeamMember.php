<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** A practitioner or staff profile shown on the About page. */
class TeamMember extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = ['name', 'slug', 'job_title', 'qualifications', 'bio', 'is_published', 'display_order'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'display_order' => 'integer'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
}
