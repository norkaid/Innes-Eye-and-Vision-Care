@props(['name' => 'image', 'label' => 'Image', 'current' => null])
<div>
    <label for="f-{{ $name }}" class="form-label">{{ $label }} <span class="font-normal text-slate">(optional)</span></label>
    @if ($current)
        <img src="{{ \App\Services\ImageUploader::url($current) }}" alt="Current image" class="mb-3 h-32 rounded-lg border border-navy/10 object-cover">
        <x-form.checkbox name="remove_image" label="Remove current image" class="mb-3" />
    @endif
    <input id="f-{{ $name }}" type="file" name="{{ $name }}" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" aria-describedby="f-{{ $name }}-hint @error($name) f-{{ $name }}-error @enderror" @error($name) aria-invalid="true" @enderror
        class="block w-full text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-navy file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-navy-700">
    <p id="f-{{ $name }}-hint" class="form-hint">JPG, PNG or WebP, maximum {{ round(config('innes.uploads.max_kb') / 1024, 1) }} MB. Use only images the centre owns or has licensed.</p>
    @error($name)<p id="f-{{ $name }}-error" class="form-error"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" /> {{ $message }}</p>@enderror
</div>
