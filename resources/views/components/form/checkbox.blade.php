@props(['name', 'label', 'checked' => false, 'hint' => null, 'value' => '1', 'id' => null])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $id ?? 'f-'.\Illuminate\Support\Str::slug($key.'-'.$value, '-');
    $isArray = str_ends_with($name, '[]');
    $base = $isArray ? substr($name, 0, -2) : $key;
    if (! old()) {
        $isChecked = $checked;
    } elseif ($isArray) {
        $isChecked = in_array((string) $value, array_map('strval', (array) old($base, [])), true);
    } else {
        $isChecked = (bool) old($key);
    }
@endphp
<div {{ $attributes->only('class')->merge(['class' => 'flex items-start gap-3']) }}>
    @if (! str_ends_with($name, '[]'))<input type="hidden" name="{{ $name }}" value="0">@endif
    <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="{{ $value }}" @checked($isChecked)
        @if ($hint) aria-describedby="{{ $id }}-hint" @endif
        {{ $attributes->except('class')->merge(['class' => 'mt-1 h-4 w-4 rounded border-slate/50 text-teal focus:ring-teal']) }}>
    <div>
        <label for="{{ $id }}" class="text-sm font-semibold text-navy">{{ $label }}</label>
        @if ($hint)<p id="{{ $id }}-hint" class="text-sm text-slate">{{ $hint }}</p>@endif
        @error($isArray ? $base : $key)<p class="form-error">{{ $message }}</p>@enderror
    </div>
</div>
