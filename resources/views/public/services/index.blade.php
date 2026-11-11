<x-layouts.public title="Eye-care services" description="Explore the eye-care services offered by {{ $site->get('business_name') }}, and request an appointment online.">
    <x-page-header title="Eye-care services" eyebrow="Services" intro="Find out what each service involves and which branches offer it, then request an appointment.">
        <form method="GET" action="{{ route('services.index') }}" class="mt-8 grid gap-3 rounded-2xl bg-white p-4 shadow-sm sm:grid-cols-[1fr_auto_auto]" role="search" aria-label="Filter services">
            <div>
                <label for="q" class="sr-only">Search services</label>
                <input id="q" name="q" type="search" value="{{ $q }}" placeholder="Search services…" class="form-input">
            </div>
            <div>
                <label for="branch" class="sr-only">Branch</label>
                <select id="branch" name="branch" class="form-input">
                    <option value="">All branches</option>
                    @foreach ($branches as $b)<option value="{{ $b->slug }}" @selected($branch?->id === $b->id)>{{ $b->name }}</option>@endforeach
                </select>
            </div>
            <button class="btn-primary" type="submit"><x-icon name="search" class="h-4 w-4" /> Search</button>
        </form>
    </x-page-header>

    <section class="container-page py-14" aria-live="polite">
        @if ($q || $branch)
            <p class="mb-6 text-sm">{{ $services->total() }} {{ \Illuminate\Support\Str::plural('service', $services->total()) }} found @if ($branch) at <strong>{{ $branch->name }}</strong>@endif @if ($q) matching “<strong>{{ $q }}</strong>”@endif. <a href="{{ route('services.index') }}">Clear filters</a></p>
        @endif
        @if ($services->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)<x-service-card :service="$service" />@endforeach
            </div>
            {{ $services->links() }}
        @else
            <x-empty-state title="No services found">Try a different search or <a href="{{ route('services.index') }}">view all services</a>.</x-empty-state>
        @endif
    </section>
    <x-cta-band />
</x-layouts.public>
