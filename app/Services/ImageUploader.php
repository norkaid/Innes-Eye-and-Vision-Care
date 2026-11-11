<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stores validated images on the configured public disk with random file names.
 * Validation (type/size) happens in the form requests via ImageUploader::rules().
 */
class ImageUploader
{
    /** @return list<string> */
    public static function rules(): array
    {
        return [
            'nullable', 'file', 'image',
            'mimes:'.implode(',', config('innes.uploads.mimes')),
            'max:'.config('innes.uploads.max_kb'),
            'dimensions:max_width=5000,max_height=5000',
        ];
    }

    public function store(UploadedFile $file, string $folder): string
    {
        return $file->store($folder, config('innes.uploads.disk'));
    }

    public function replace(?UploadedFile $file, ?string $existing, string $folder, bool $remove = false): ?string
    {
        if ($file) {
            $this->delete($existing);

            return $this->store($file, $folder);
        }
        if ($remove) {
            $this->delete($existing);

            return null;
        }

        return $existing;
    }

    public function delete(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'images/')) {
            Storage::disk(config('innes.uploads.disk'))->delete($path);
        }
    }

    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        // Bundled placeholder artwork lives in /public/images.
        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return Storage::disk(config('innes.uploads.disk'))->url($path);
    }
}
