@props(['name' => 'image', 'label' => 'Image', 'current' => null, 'folder' => 'general'])
@php
    $library = \App\Models\Media::query()
        ->orderByRaw('case when folder = ? then 0 else 1 end', [$folder])
        ->latest()->limit(60)->get();
    $selected = (int) old('image_media_id');
@endphp
<div>
    <label for="f-{{ $name }}" class="form-label">{{ $label }} <span class="font-normal text-slate">(optional)</span></label>
    @if ($current)
        <img src="{{ \App\Services\ImageUploader::url($current) }}" alt="Current image" class="mb-3 h-32 rounded-lg border border-navy/10 object-cover">
        <x-form.checkbox name="remove_image" label="Remove current image" class="mb-3" />
    @endif
    <input id="f-{{ $name }}" type="file" name="{{ $name }}" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" aria-describedby="f-{{ $name }}-hint @error($name) f-{{ $name }}-error @enderror" @error($name) aria-invalid="true" @enderror
        class="block w-full text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-navy file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-navy-700">
    <p id="f-{{ $name }}-hint" class="form-hint">Upload a new picture (JPG, PNG or WebP, maximum {{ round(config('innes.uploads.max_kb') / 1024, 1) }} MB; it is also added to the media library), or choose one already in the library below. Use only images the centre owns or has licensed.</p>
    @error($name)<p id="f-{{ $name }}-error" class="form-error"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" /> {{ $message }}</p>@enderror

    @if ($library->isNotEmpty())
        <details class="mt-3 rounded-lg border border-navy/10 bg-white p-3" @if ($selected) open @endif>
            <summary class="cursor-pointer text-sm font-semibold text-navy">Choose from media library ({{ $library->count() }})</summary>
            <fieldset class="mt-3">
                <legend class="sr-only">Media library images</legend>
                <div class="grid max-h-80 grid-cols-3 gap-2 overflow-y-auto sm:grid-cols-4 md:grid-cols-6">
                    <label class="flex cursor-pointer flex-col items-center justify-center rounded-lg border border-navy/15 p-2 text-center text-xs has-[:checked]:border-teal has-[:checked]:ring-2 has-[:checked]:ring-teal">
                        <input type="radio" name="image_media_id" value="" class="sr-only" @checked(! $selected)>
                        Keep as is
                    </label>
                    @foreach ($library as $m)
                        <label class="cursor-pointer overflow-hidden rounded-lg border border-navy/15 has-[:checked]:border-teal has-[:checked]:ring-2 has-[:checked]:ring-teal" title="{{ $m->original_name }}">
                            <input type="radio" name="image_media_id" value="{{ $m->id }}" class="sr-only" @checked($selected === $m->id)>
                            <img src="{{ $m->url() }}" alt="{{ $m->alt_text ?: $m->original_name }}" loading="lazy" class="aspect-square w-full bg-sand object-cover">
                        </label>
                    @endforeach
                </div>
            </fieldset>
            @error('image_media_id')<p class="form-error"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" /> {{ $message }}</p>@enderror
        </details>
    @endif
</div>
