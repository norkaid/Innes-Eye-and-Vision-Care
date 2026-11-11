@props(['title', 'icon' => 'search'])
<div {{ $attributes->merge(['class' => 'rounded-2xl border-2 border-dashed border-navy/15 bg-white px-6 py-14 text-center']) }}>
    <x-icon :name="$icon" class="mx-auto h-10 w-10 text-teal" />
    <h3 class="mt-3 text-lg font-semibold">{{ $title }}</h3>
    @if (trim($slot))<div class="mx-auto mt-2 max-w-md text-sm">{{ $slot }}</div>@endif
</div>
