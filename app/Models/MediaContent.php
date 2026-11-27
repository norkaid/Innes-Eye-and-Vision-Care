<?php

namespace App\Models;

use App\Services\MediaStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The image file itself, stored in the database. */
class MediaContent extends Model
{
    protected $primaryKey = 'media_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['media_id', 'data'];

    /** PostgreSQL returns binary columns as streams: read them once into a string. */
    protected static function booted(): void
    {
        static::retrieved(function (MediaContent $content) {
            $raw = $content->getAttributes()['data'] ?? null;
            if (is_resource($raw)) {
                $content->setRawAttributes(['data' => MediaStore::bytes($raw)] + $content->getAttributes(), true);
            }
        });
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
