<x-layouts.public title="Branches" description="Find a {{ $site->get('business_name') }} branch: addresses, opening hours, directions and services.">
    <x-page-header title="Our branches" eyebrow="Find us" intro="Opening hours, contact details, directions and the services available at each branch." />
    <section class="container-page py-14">
        @if ($branches->isNotEmpty())
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($branches as $branch)<x-branch-card :branch="$branch" />@endforeach
            </div>
        @else
            <x-empty-state title="Branch details coming soon" icon="map-pin">Please use the contact form while we confirm branch details.</x-empty-state>
        @endif
    </section>
    <x-cta-band />
</x-layouts.public>
