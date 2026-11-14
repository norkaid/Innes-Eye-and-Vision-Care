<x-layouts.admin title="Media library">
    <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="card mb-6 space-y-4 p-6" novalidate>
        @csrf
        <h2 class="text-lg font-semibold">Upload images</h2>
        <p class="text-sm">Upload the logo, photos of glasses and frames, branch photos and other pictures. Choose a folder to keep them organised. Up to 20 files at once; JPG, PNG or WebP, maximum {{ round(config('innes.uploads.max_kb') / 1024, 1) }} MB each. Use only images the centre owns or has licensed.</p>
        <div class="grid gap-4 md:grid-cols-3">
            <x-form.select name="folder" label="Folder" required :options="$folders" :value="$folder ?? 'products'" />
            <x-form.input name="alt_text" label="Description (alt text)" hint="Describes the picture for screen readers, e.g. “Black rectangular acetate frame”." />
            <div>
                <label for="f-files" class="form-label">Images</label>
                <input id="f-files" type="file" name="files[]" multiple required accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    class="block w-full text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-navy file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-navy-700">
                @foreach (['files', ...collect($errors->keys())->filter(fn ($k) => str_starts_with($k, 'files.'))->all()] as $key)
                    @error($key)<p class="form-error"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" /> {{ $message }}</p>@enderror
                @endforeach
            </div>
        </div>
        <button class="btn-primary" type="submit"><x-icon name="plus" class="h-4 w-4" /> Upload</button>
    </form>

    <nav class="mb-4 flex flex-wrap gap-2 text-sm" aria-label="Media folders">
        <a href="{{ route('admin.media.index') }}" class="rounded-full px-3 py-1 font-semibold no-underline {{ ! $folder ? 'bg-navy text-white' : 'bg-white text-navy' }}">All ({{ $counts->sum() }})</a>
        @foreach ($folders as $key => $label)
            <a href="{{ route('admin.media.index', ['folder' => $key]) }}" class="rounded-full px-3 py-1 font-semibold no-underline {{ $folder === $key ? 'bg-navy text-white' : 'bg-white text-navy' }}">{{ $label }} ({{ $counts[$key] ?? 0 }})</a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('admin.media.index') }}" role="search" class="mb-4 flex flex-wrap items-end gap-3">
        @if ($folder)<input type="hidden" name="folder" value="{{ $folder }}">@endif
        <div class="min-w-56 flex-1"><label for="q" class="sr-only">Search images</label><input id="q" name="q" type="search" value="{{ $q }}" placeholder="Search by file name or description" class="form-input"></div>
        <button class="btn-secondary" type="submit"><x-icon name="search" class="h-4 w-4" /> Search</button>
    </form>

    @if ($media->isEmpty())
        <x-empty-state title="No images here yet" icon="glasses">Upload images with the form above.</x-empty-state>
    @else
        <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @foreach ($media as $item)
                <li class="card flex flex-col overflow-hidden" x-data="{ copied: false }">
                    <a href="{{ $item->url() }}" target="_blank" rel="noopener" class="block bg-sand">
                        <img src="{{ $item->url() }}" alt="{{ $item->alt_text ?? '' }}" loading="lazy" class="aspect-square w-full object-contain">
                    </a>
                    <div class="flex flex-1 flex-col gap-1 p-3 text-xs">
                        <p class="truncate font-semibold text-navy" title="{{ $item->original_name }}">{{ $item->original_name }}</p>
                        <p>{{ $item->folderLabel() }} · {{ $item->humanSize() }}@if ($item->width) · {{ $item->width }}×{{ $item->height }}@endif</p>
                        <div class="mt-auto flex flex-wrap gap-1 pt-2">
                            <button type="button" class="btn-secondary btn-sm" @click="navigator.clipboard.writeText(@js($item->url())); copied = true; setTimeout(() => copied = false, 1500)">
                                <span x-text="copied ? 'Copied' : 'Copy link'">Copy link</span>
                            </button>
                            <a href="{{ route('admin.media.edit', $item) }}" class="btn-secondary btn-sm">Edit</a>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $media->links() }}</div>
    @endif
</x-layouts.admin>
