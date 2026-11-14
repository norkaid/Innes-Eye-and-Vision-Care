<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\AuditLogger;
use App\Services\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Media library: the logo, glasses photos and other website images, sorted into folders.
 */
class MediaController extends Controller
{
    public function __construct(private ImageUploader $images, private AuditLogger $audit) {}

    public function index(Request $request)
    {
        $folders = config('innes.media_folders');
        $folder = array_key_exists($request->string('folder')->toString(), $folders) ? $request->string('folder')->toString() : null;
        $q = $request->string('q')->limit(100)->toString();

        return view('admin.media.index', [
            'media' => Media::query()->when($folder, fn ($query) => $query->where('folder', $folder))
                ->search($q ?: null)->latest()->paginate(36)->withQueryString(),
            'folders' => $folders,
            'counts' => Media::query()->selectRaw('folder, count(*) as total')->groupBy('folder')->pluck('total', 'folder'),
            'folder' => $folder,
            'q' => $q,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'folder' => ['required', Rule::in(array_keys(config('innes.media_folders')))],
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => array_values(array_diff(ImageUploader::rules(), ['nullable'])),
            'alt_text' => ['nullable', 'string', 'max:255'],
        ], [], ['files.*' => 'image']);

        foreach ($request->file('files') as $file) {
            $media = $this->images->storeMedia($file, $data['folder'], $data['alt_text'] ?? null);
            $this->audit->log('media.uploaded', $media, ['folder' => $media->folder, 'file' => $media->original_name]);
        }
        $count = count($request->file('files'));

        return redirect()->route('admin.media.index', ['folder' => $data['folder']])
            ->with('success', $count === 1 ? 'Image uploaded.' : "{$count} images uploaded.");
    }

    public function edit(Media $medium)
    {
        return view('admin.media.edit', ['item' => $medium, 'usages' => $medium->usages(), 'folders' => config('innes.media_folders')]);
    }

    public function update(Request $request, Media $medium)
    {
        $data = $request->validate([
            'original_name' => ['required', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'folder' => ['required', Rule::in(array_keys(config('innes.media_folders')))],
        ]);
        // The folder is a label for sorting; the file itself stays where it is so links keep working.
        $medium->update($data);
        $this->audit->log('media.updated', $medium, ['folder' => $medium->folder]);

        return redirect()->route('admin.media.index', ['folder' => $medium->folder])->with('success', 'Image details saved.');
    }

    public function destroy(Media $medium)
    {
        $usages = $medium->usages();
        if ($usages) {
            return back()->with('error', 'This image is still used ('.implode('; ', $usages).'). Choose another image there first.');
        }
        Storage::disk(config('innes.uploads.disk'))->delete($medium->path);
        $this->audit->log('media.deleted', $medium, ['file' => $medium->original_name]);
        $medium->delete();

        return redirect()->route('admin.media.index', ['folder' => $medium->folder])->with('success', 'Image deleted.');
    }
}
