<x-layouts.public :title="$service->name" :description="$service->short_description">
    <x-page-header :title="$service->name" :intro="$service->short_description">
        <x-slot:breadcrumbs><x-breadcrumbs :items="['Services' => route('services.index'), $service->name => null]" /></x-slot:breadcrumbs>
        <div class="mt-6"><a href="{{ route('appointments.create', ['service' => $service->slug]) }}" class="btn-primary"><x-icon name="calendar" class="h-4 w-4" /> Request an appointment</a></div>
    </x-page-header>

    <section class="container-page grid gap-12 py-14 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <img src="{{ \App\Services\ImageUploader::url($service->image_path) ?? asset('images/placeholder-service.svg') }}" alt="{{ $service->image_path ? $service->name : '' }}" class="mb-8 aspect-[16/9] w-full rounded-2xl object-cover" width="800" height="450">
            <h2 class="font-display text-2xl font-bold">What the service involves</h2>
            <div class="prose-content mt-2">{!! nl2br(e($service->description ?: 'Further details will be added once confirmed by the centre.')) !!}</div>

            @if ($service->preparation)
                <h2 class="mt-10 font-display text-2xl font-bold">How to prepare</h2>
                <div class="prose-content mt-2">{!! nl2br(e($service->preparation)) !!}</div>
            @endif

            <x-alert type="info" title="Medical advice" class="mt-10">This information is general guidance. It is not a substitute for a professional examination or diagnosis.</x-alert>
        </div>
        <aside class="space-y-6">
            <div class="card p-6">
                <h2 class="text-lg font-semibold">Available at</h2>
                @if ($service->branches->isNotEmpty())
                    <ul class="mt-3 divide-y divide-navy/10">
                        @foreach ($service->branches as $b)
                            <li class="flex items-center justify-between gap-3 py-3">
                                <a href="{{ route('branches.show', $b->slug) }}" class="font-semibold">{{ $b->name }}</a>
                                <a href="{{ route('appointments.create', ['service' => $service->slug, 'branch' => $b->slug]) }}" class="btn-text text-sm">Book <span class="sr-only">at {{ $b->name }}</span></a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-2 text-sm">Please contact us to check which branches offer this service.</p>
                @endif
            </div>
            @if ($related->isNotEmpty())
                <div class="card p-6">
                    <h2 class="text-lg font-semibold">Other services</h2>
                    <ul class="mt-3 space-y-2">
                        @foreach ($related as $r)<li><a href="{{ route('services.show', $r->slug) }}">{{ $r->name }}</a></li>@endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </section>
</x-layouts.public>
