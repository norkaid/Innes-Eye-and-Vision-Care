<?php

namespace App\Services;

/**
 * Makes uploaded photos web-sized before they are stored in the database:
 * turns phone photos the right way up, shrinks anything larger than
 * config('innes.uploads.max_dimension') pixels and re-compresses large files.
 * PNG/WebP transparency (e.g. logos) is kept. If the GD extension is missing,
 * the original file is stored unchanged.
 */
class ImageOptimizer
{
    /** Files smaller than this, already within the size limit, are stored as they are. */
    private const KEEP_BELOW_BYTES = 1_500_000;

    /**
     * @return array{data: string, mime: string, extension: string, width: ?int, height: ?int}
     */
    public function optimize(string $path, string $mime): array
    {
        $original = (string) file_get_contents($path);
        $info = @getimagesize($path);
        [$width, $height] = $info ? [$info[0], $info[1]] : [null, null];
        $result = ['data' => $original, 'mime' => $mime, 'extension' => $this->extension($mime), 'width' => $width, 'height' => $height];

        if (! $width || ! $height || ! function_exists('imagecreatefromstring')) {
            return $result;
        }

        $maxSide = (int) config('innes.uploads.max_dimension', 2000);
        $orientation = $mime === 'image/jpeg' ? $this->exifOrientation($path) : 1;
        $tooBig = max($width, $height) > $maxSide;
        if (! $tooBig && $orientation === 1 && strlen($original) < self::KEEP_BELOW_BYTES) {
            return $result;
        }

        // Decoding a large photo needs width x height x 4 bytes of memory.
        $previousLimit = ini_get('memory_limit');
        @ini_set('memory_limit', '512M');

        try {
            $image = @imagecreatefromstring($original);
            if (! $image) {
                return $result;
            }

            $image = $this->rotate($image, $orientation);
            $w = imagesx($image);
            $h = imagesy($image);
            if (max($w, $h) > $maxSide) {
                $scale = $maxSide / max($w, $h);
                $resized = imagescale($image, max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale)), IMG_BICUBIC);
                if ($resized) {
                    imagedestroy($image);
                    $image = $resized;
                }
            }

            $encoded = $this->encode($image, $mime);
            $newWidth = imagesx($image);
            $newHeight = imagesy($image);
            imagedestroy($image);

            // Keep the original if re-encoding did not help and no resize/rotation was needed.
            if ($encoded === null || (! $tooBig && $orientation === 1 && strlen($encoded['data']) >= strlen($original))) {
                return $result;
            }

            return $encoded + ['width' => $newWidth, 'height' => $newHeight];
        } finally {
            @ini_set('memory_limit', (string) $previousLimit);
        }
    }

    /** @return array{data: string, mime: string, extension: string}|null */
    private function encode(\GdImage $image, string $mime): ?array
    {
        ob_start();
        if ($mime === 'image/png') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $ok = imagepng($image, null, 8);
        } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $ok = imagewebp($image, null, 82);
        } else {
            imageinterlace($image, true);
            $ok = imagejpeg($image, null, 82);
            $mime = 'image/jpeg';
        }
        $data = (string) ob_get_clean();

        return $ok && $data !== '' ? ['data' => $data, 'mime' => $mime, 'extension' => $this->extension($mime)] : null;
    }

    private function exifOrientation(string $path): int
    {
        if (! function_exists('exif_read_data')) {
            return 1;
        }
        $exif = @exif_read_data($path);

        return is_array($exif) && isset($exif['Orientation']) ? (int) $exif['Orientation'] : 1;
    }

    private function rotate(\GdImage $image, int $orientation): \GdImage
    {
        $angle = match ($orientation) {
            3, 4 => 180,
            5, 6 => -90,
            7, 8 => 90,
            default => 0,
        };
        if ($angle !== 0) {
            $rotated = imagerotate($image, $angle, 0);
            if ($rotated) {
                imagedestroy($image);
                $image = $rotated;
            }
        }
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        return $image;
    }

    private function extension(string $mime): string
    {
        return match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }
}
