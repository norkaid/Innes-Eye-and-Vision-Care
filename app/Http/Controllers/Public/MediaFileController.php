<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Serves media library images from the database. Paths are random and an image's
 * bytes never change, so browsers may cache them for a year.
 */
class MediaFileController extends Controller
{
    private const SAFE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __invoke(Request $request, string $path): Response
    {
        $media = Media::where('path', 'media/'.$path)->firstOrFail();
        $etag = '"'.md5($media->path.$media->size_bytes).'"';

        $headers = [
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'ETag' => $etag,
        ];
        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, $headers);
        }

        $data = $media->content()->value('data');
        abort_if($data === null, 404);
        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }
        $type = in_array($media->mime_type, self::SAFE_TYPES, true) ? $media->mime_type : 'application/octet-stream';

        return response($data, 200, $headers + [
            'Content-Type' => $type,
            'Content-Length' => (string) strlen($data),
            'Content-Disposition' => 'inline',
        ]);
    }
}
