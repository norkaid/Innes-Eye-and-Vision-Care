<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores validated images in the database (media + media_contents tables) under a
 * random path such as media/products/<random>.jpg, served by MediaFileController at
 * /media/... Library images are only deleted from the Media page, so replacing an
 * image never breaks another page using it.
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
            'dimensions:max_width=12000,max_height=12000',
        ];
    }

    /**
     * The largest upload that will actually get through, in KB: the site's own limit
     * or PHP's upload_max_filesize / post_max_size, whichever is smaller.
     */
    public static function effectiveMaxKb(): int
    {
        return (int) min(
            config('innes.uploads.max_kb'),
            self::iniBytes((string) ini_get('upload_max_filesize')) / 1024 ?: PHP_INT_MAX,
            self::iniBytes((string) ini_get('post_max_size')) / 1024 ?: PHP_INT_MAX,
        );
    }

    /** Human-readable upload limit, e.g. "10 MB". */
    public static function maxLabel(): string
    {
        $kb = self::effectiveMaxKb();

        return $kb >= 1024 ? rtrim(rtrim(number_format($kb / 1024, 1), '0'), '.').' MB' : $kb.' KB';
    }

    /**
     * Problems in the PHP set-up that stop image uploads working, with how to fix each.
     *
     * @return list<string>
     */
    public static function serverProblems(): array
    {
        $problems = [];
        $ini = php_ini_loaded_file() ?: 'php.ini';
        if (! filter_var(ini_get('file_uploads'), FILTER_VALIDATE_BOOL)) {
            $problems[] = "File uploads are switched off in PHP. In {$ini} set: file_uploads = On";
        }
        $tmp = self::uploadTempDir();
        if (! is_dir($tmp) || ! is_writable($tmp)) {
            $problems[] = "PHP's temporary upload folder \"{$tmp}\" does not exist or cannot be written to, so every upload fails. In {$ini} set upload_tmp_dir to a folder that exists, e.g.: upload_tmp_dir = \"".(PHP_OS_FAMILY === 'Windows' ? 'C:\\Windows\\Temp' : '/tmp').'"';
        }
        if (! extension_loaded('fileinfo')) {
            $problems[] = "The PHP \"fileinfo\" extension is off, so uploaded files cannot be checked. In {$ini} remove the ; in front of: extension=fileinfo";
        }
        if (! extension_loaded('gd')) {
            $problems[] = "The PHP \"gd\" extension is off, so large photos cannot be shrunk automatically. In {$ini} remove the ; in front of: extension=gd";
        }
        $wantedKb = (int) config('innes.uploads.max_kb');
        if ($wantedKb < 5120) {
            $problems[] = 'The website itself only accepts pictures up to '.round($wantedKb / 1024, 1).' MB. In the .env file set: UPLOAD_MAX_KB=10240 (then run php artisan config:clear)';
        }
        $uploadKb = self::iniBytes((string) ini_get('upload_max_filesize')) / 1024;
        $postKb = self::iniBytes((string) ini_get('post_max_size')) / 1024;
        if ($uploadKb && $uploadKb < $wantedKb) {
            $problems[] = 'PHP only accepts files up to '.ini_get('upload_max_filesize')." each, so most phone photos are refused. In {$ini} set: upload_max_filesize = 20M";
        }
        if ($postKb && $postKb < $wantedKb * 2) {
            $problems[] = 'PHP only accepts '.ini_get('post_max_size')." per upload in total. In {$ini} set: post_max_size = 64M";
        }

        return $problems;
    }

    /** The folder PHP writes uploads to before the site reads them. */
    public static function uploadTempDir(): string
    {
        $dir = (string) ini_get('upload_tmp_dir');

        return $dir !== '' ? $dir : sys_get_temp_dir();
    }

    /** A specific explanation for PHP's upload error codes. */
    public static function uploadErrorMessage(?int $error, string $fallback): string
    {
        $ini = php_ini_loaded_file() ?: 'php.ini';
        $tooBig = 'The image could not be uploaded because it is bigger than PHP allows (upload_max_filesize = '.ini_get('upload_max_filesize')."). Choose a smaller picture, or raise upload_max_filesize in {$ini} and restart the site.";

        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => $tooBig,
            UPLOAD_ERR_PARTIAL => 'The image was only partly received (the connection was interrupted). Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => "The image could not be uploaded because PHP has no temporary folder for uploads. In {$ini} set upload_tmp_dir to a folder that exists, then restart the site.",
            UPLOAD_ERR_CANT_WRITE => 'The image could not be uploaded because PHP could not save it to its temporary folder ('.self::uploadTempDir()."). Check that the folder exists and has free space, or set upload_tmp_dir in {$ini} to another folder, then restart the site.",
            UPLOAD_ERR_EXTENSION => 'The image could not be uploaded because a PHP extension blocked it. Check the PHP settings.',
            default => str_replace(':max_upload', self::maxLabel(), $fallback).' (PHP upload error code: '.($error ?? 'unknown').')',
        };
    }

    private static function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '0' || $value === '-1') {
            return 0;
        }
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
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
        $image = app(ImageOptimizer::class)->optimize($file->getRealPath(), (string) $file->getMimeType());
        $path = "media/{$folder}/".Str::random(40).'.'.$image['extension'];
        $data = $image['data'];

        return DB::transaction(function () use ($file, $folder, $altText, $image, $path, $data) {
            $media = Media::create([
                'folder' => $folder,
                'path' => $path,
                'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                'alt_text' => $altText,
                'mime_type' => $image['mime'],
                'size_bytes' => strlen($data),
                'width' => $image['width'],
                'height' => $image['height'],
                'uploaded_by' => Auth::id(),
            ]);
            $media->content()->create(['data' => $data]);

            return $media;
        });
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
        // Media library images are stored in the database and served at /media/...
        if (str_starts_with($path, 'media/')) {
            return url($path);
        }

        return Storage::disk(config('innes.uploads.disk'))->url($path);
    }
}
