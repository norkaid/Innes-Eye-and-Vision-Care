<x-layouts.admin title="Edit image">
    <x-slot:breadcrumb><a href="{{ route('admin.media.index', ['folder' => $item->folder]) }}">Media library</a></x-slot:breadcrumb>
    <div class="grid gap-6 lg:grid-cols-[2fr_3fr]">
        <div class="card overflow-hidden">
            <img src="{{ $item->url() }}" alt="{{ $item->alt_text ?? '' }}" class="w-full bg-sand object-contain">
            <dl class="space-y-1 p-4 text-sm">
                <div><dt class="inline font-semibold text-navy">Size:</dt> <dd class="inline">{{ $item->humanSize() }}@if ($item->width) · {{ $item->width }}×{{ $item->height }} px @endif</dd></div>
                <div><dt class="inline font-semibold text-navy">Uploaded:</dt> <dd class="inline">{{ $item->created_at->format('j M Y, H:i') }}@if ($item->uploader) by {{ $item->uploader->name }}@endif</dd></div>
                <div><dt class="font-semibold text-navy">Link:</dt> <dd class="break-all"><a href="{{ $item->url() }}" target="_blank" rel="noopener">{{ $item->url() }}</a></dd></div>
            </dl>
        </div>
        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.media.update', $item) }}" class="card space-y-5 p-6" novalidate>
                @csrf @method('PATCH')
                <x-form.input name="original_name" label="Name" required :value="$item->original_name" />
                <x-form.input name="alt_text" label="Description (alt text)" :value="$item->alt_text" hint="Describes the picture for people using screen readers." />
                <x-form.select name="folder" label="Folder" required :options="$folders" :value="$item->folder" />
                <button class="btn-primary" type="submit">Save</button>
            </form>
            <div class="card space-y-3 p-6">
                <h2 class="text-lg font-semibold">Where this image is used</h2>
                @if ($usages)
                    <ul class="list-disc pl-5 text-sm">@foreach ($usages as $use)<li>{{ $use }}</li>@endforeach</ul>
                    <p class="text-sm">Choose a different image in those places before deleting this one.</p>
                @else
                    <p class="text-sm">Not used anywhere on the website.</p>
                    <x-admin.delete-button :action="route('admin.media.destroy', $item)" label="Delete image" confirm="Delete this image permanently?" class="btn-danger" />
                @endif
            </div>
        </div>
    </div>
</x-layouts.admin>
