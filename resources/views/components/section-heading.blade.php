@props(['title', 'eyebrow' => null, 'intro' => null, 'id' => null])
<div {{ $attributes->merge(['class' => 'max-w-2xl']) }}>
    @if ($eyebrow)<p class="text-sm font-semibold uppercase tracking-wider text-teal-700">{{ $eyebrow }}</p>@endif
    <h2 @if ($id) id="{{ $id }}" @endif class="mt-1 font-display text-2xl font-bold sm:text-3xl">{{ $title }}</h2>
    @if ($intro)<p class="mt-3 leading-7">{{ $intro }}</p>@endif
</div>
