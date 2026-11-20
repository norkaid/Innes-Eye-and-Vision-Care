{{-- Radio grid for choosing a media library image (or none). Submits the media id, or "none". --}}
@props(['name', 'label', 'current' => null, 'items', 'hint' => null])
<fieldset>
    <legend class="form-label">{{ $label }}</legend>
    @if ($hint)<p class="form-hint mb-2">{{ $hint }}</p>@endif
    <div class="grid max-h-72 grid-cols-3 gap-2 overflow-y-auto sm:grid-cols-5 lg:grid-cols-6">
        <label class="flex cursor-pointer items-center justify-center rounded-lg border border-navy/15 p-2 text-center text-xs has-[:checked]:border-teal has-[:checked]:ring-2 has-[:checked]:ring-teal">
            <input type="radio" name="{{ $name }}" value="none" class="sr-only" @checked(! $current)>
            Standard image
        </label>
        @foreach ($items as $m)
            <label class="cursor-pointer overflow-hidden rounded-lg border border-navy/15 has-[:checked]:border-teal has-[:checked]:ring-2 has-[:checked]:ring-teal" title="{{ $m->original_name }}">
                <input type="radio" name="{{ $name }}" value="{{ $m->id }}" class="sr-only" @checked($current === $m->path)>
                <img src="{{ $m->url() }}" alt="{{ $m->alt_text ?: $m->original_name }}" loading="lazy" class="aspect-[4/3] w-full bg-sand object-cover">
            </label>
        @endforeach
    </div>
    @error($name)<p class="form-error"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" /> {{ $message }}</p>@enderror
</fieldset>
