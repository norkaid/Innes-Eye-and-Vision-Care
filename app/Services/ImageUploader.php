<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Stores validated images on the configured public disk with random file names and
 * records each one in the media library. Files in the library (media/...) are only
 * deleted from the Media page, so replacing an image never breaks another page using it.
 * Validation (type/size) happens in the controllers via ImageUploader::rules().
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

    /** Validation rules for the image fields shared by the content forms. */
    public static function fieldRules(): array
    {
        return [
            'image' => self::rules(),
            'image_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'remove_image' => ['boolean'],
        ];
    }

    /** Uploads a file into the media library and returns the stored path. */
    public function store(UploadedFile $file, string $folder, ?string $altText = null): string
    {
        return $this->storeMedia($file, $folder, $altText)->path;
    }

    public function storeMedia(UploadedFile $file, string $folder, ?string $altText = null): Media
    {
        if (! array_key_exists($folder, config('innes.media_folders'))) {
            $folder = 'general';
        }
        $size = @getimagesize($file->getRealPath()) ?: [null, null];
        $path = $file->store("media/{$folder}", config('innes.uploads.disk'));

        return Media::create([
            'folder' => $folder,
            'path' => $path,
            'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
            'alt_text' => $altText,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => (int) $file->getSize(),
            'width' => $size[0],
            'height' => $size[1],
            'uploaded_by' => Auth::id(),
        ]);
    }

    /**
     * Works out the new image path for a content form: a new upload, a picture chosen
     * from the media library, removal, or the existing image unchanged.
     */
    public function fromRequest(Request $request, ?string $existing, string $folder): ?string
    {
        if ($request->hasFile('image')) {
            $this->delete($existing);

            return $this->store($request->file('image'), $folder);
        }
        if ($id = $request->integer('image_media_id')) {
            $media = Media::find($id);
            if ($media) {
                if ($media->path !== $existing) {
                    $this->delete($existing);
                }

                return $media->path;
            }
        }
        if ($request->boolean('remove_image')) {
            $this->delete($existing);

            return null;
        }

        return $existing;
    }

    /** Deletes a stray (pre-library) upload. Library files and bundled artwork are kept. */
    public function delete(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'images/') && ! str_starts_with($path, 'media/')) {
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
