<?php

namespace App\Models;

use App\Services\ImageUploader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['folder', 'path', 'original_name', 'alt_text', 'mime_type', 'size_bytes', 'width', 'height', 'uploaded_by'];

    protected function casts(): array
    {
        return ['size_bytes' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return ImageUploader::url($this->path);
    }

    public function folderLabel(): string
    {
        return config("innes.media_folders.{$this->folder}", ucfirst($this->folder));
    }

    public function humanSize(): string
    {
        return $this->size_bytes >= 1048576
            ? round($this->size_bytes / 1048576, 1).' MB'
            : max(1, round($this->size_bytes / 1024)).' KB';
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $term ? $query->where(fn ($q) => $q->where('original_name', 'like', "%{$term}%")->orWhere('alt_text', 'like', "%{$term}%")) : $query;
    }

    /**
     * Where this file is currently used on the website.
     *
     * @return list<string>
     */
    public function usages(): array
    {
        $uses = [];
        foreach (Product::withTrashed()->where('image_path', $this->path)->pluck('name') as $name) {
            $uses[] = "Product: {$name}";
        }
        foreach (Service::withTrashed()->where('image_path', $this->path)->pluck('name') as $name) {
            $uses[] = "Service: {$name}";
        }
        foreach (HealthArticle::withTrashed()->where('featured_image_path', $this->path)->pluck('title') as $title) {
            $uses[] = "Article: {$title}";
        }
        if (BusinessSetting::where('key', 'logo_path')->where('value', $this->path)->exists()) {
            $uses[] = 'Website logo';
        }

        return $uses;
    }
}
