<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'rank'];

    protected function casts(): array
    {
        return ['rank' => 'integer'];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /** @return list<string> */
    public function permissions(): array
    {
        return config("innes.roles.{$this->slug}.permissions", []);
    }
}
