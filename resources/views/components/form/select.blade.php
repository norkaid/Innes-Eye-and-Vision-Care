@props(['name', 'label', 'options' => [], 'value' => null, 'hint' => null, 'required' => false, 'placeholder' => null, 'id' => null])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $id ?? 'f-'.\Illuminate\Support\Str::slug($key, '-');
    $hasError = $errors->has($key);
    $describedBy = collect([$hint ? $id.'-hint' : null, $hasError ? $id.'-error' : null])->filter()->join(' ');
    $current = (string) old($key, $value instanceof \BackedEnum ? $value->value : $value);
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="form-label">{{ $label }}@if ($required) <span class="text-red-700" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif</label>
    <select id="{{ $id }}" name="{{ $name }}" @if ($required) required @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($hasError) aria-invalid="true" @endif
        {{ $attributes->except('class')->merge(['class' => 'form-input']) }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $optValue => $optLabel)
            <option value="{{ $optValue }}" @selected($current === (string) $optValue)>{{ $optLabel }}</option>
        @endforeach
        {{ $slot }}
    </select>
    @if ($hint)<p id="{{ $id }}-hint" class="form-hint">{{ $hint }}</p>@endif
    @error($key)<p id="{{ $id }}-error" class="form-error"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" /> {{ $message }}</p>@enderror
</div>
