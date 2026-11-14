<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The image file itself, stored in the database. */
class MediaContent extends Model
{
    protected $primaryKey = 'media_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['media_id', 'data'];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
