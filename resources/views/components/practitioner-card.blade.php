{{-- Use only for practitioner profiles that the business has supplied and approved. --}}
@props(['name', 'role' => null, 'bio' => null, 'image' => null])
<article class="card p-6 text-center">
    @if ($image)<img src="{{ $image }}" alt="Portrait of {{ $name }}" loading="lazy" class="mx-auto h-28 w-28 rounded-full object-cover">@endif
    <h3 class="mt-4 text-lg font-semibold">{{ $name }}</h3>
    @if ($role)<p class="text-sm font-semibold text-teal-700">{{ $role }}</p>@endif
    @if ($bio)<p class="mt-2 text-sm">{{ $bio }}</p>@endif
</article>
