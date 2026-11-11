@props(['branch'])
<article class="card relative flex flex-col p-6">
    <div class="flex items-start gap-3">
        <span class="rounded-full bg-sky p-2.5 text-teal-700"><x-icon name="map-pin" /></span>
        <div>
            <h3 class="text-lg font-semibold"><a href="{{ route('branches.show', $branch->slug) }}" class="text-navy no-underline after:absolute after:inset-0 hover:underline">{{ $branch->name }}</a></h3>
            @if ($branch->city)<p class="text-sm">{{ collect([$branch->city, $branch->region])->filter()->join(', ') }}</p>@endif
        </div>
    </div>
    <dl class="mt-4 space-y-2 text-sm">
        @if ($branch->address)<div class="flex gap-2"><dt class="sr-only">Address</dt><dd>{{ $branch->address }}</dd></div>@endif
        @if ($branch->phone)<div class="flex gap-2"><dt class="font-semibold text-navy">Phone:</dt><dd>{{ $branch->phone }}</dd></div>@endif
    </dl>
    <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-teal-700">Hours, services &amp; directions <x-icon name="arrow-right" class="h-4 w-4" /></span>
</article>
