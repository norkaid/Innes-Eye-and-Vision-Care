<x-layouts.public title="About us" description="Learn about {{ $site->get('business_name') }}, an eye-care and optical services provider in Ghana.">
    <x-page-header title="About {{ $site->get('business_name') }}" eyebrow="About us" intro="{{ $site->get('tagline') }}." />

    <section class="container-page grid gap-12 py-16 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <h2 class="font-display text-2xl font-bold">Our story</h2>
            @if ($site->get('about_intro'))
                <div class="prose-content">{!! nl2br(e($site->get('about_intro'))) !!}</div>
            @else
                <x-alert type="warning" title="Content requires business confirmation">
                    The centre's verified background (history, establishment date, branches and team) will appear here once supplied and approved.
                    Staff can add it in <em>Admin → Settings → About page</em>.
                </x-alert>
            @endif

            <div class="grid gap-6 sm:grid-cols-3">
                @foreach (['mission' => 'Mission', 'vision' => 'Vision', 'values' => 'Values'] as $key => $label)
                    <div class="rounded-2xl bg-sky/60 p-6">
                        <h3 class="text-lg font-semibold">Our {{ strtolower($label) }}</h3>
                        @if ($site->get($key))
                            <p class="mt-2 text-sm leading-6">{!! nl2br(e($site->get($key))) !!}</p>
                        @else
                            <p class="mt-2 text-sm italic leading-6">To be confirmed by the centre.</p>
                        @endif
                    </div>
                @endforeach
            </div>

            <h2 class="font-display pt-4 text-2xl font-bold">Our team</h2>
            <p>Practitioner and team profiles will be published here only with each person's approval and verified qualifications.</p>
        </div>
        <aside class="space-y-6">
            <img src="{{ asset('images/placeholder-service.svg') }}" alt="" class="w-full rounded-2xl" loading="lazy">
            <div class="card p-6">
                <h2 class="text-lg font-semibold">Visit us</h2>
                <p class="mt-2 text-sm">Find opening hours, directions and services for each branch.</p>
                <a href="{{ route('branches.index') }}" class="btn-primary mt-4 w-full">Find a branch</a>
            </div>
        </aside>
    </section>
    <x-cta-band />
</x-layouts.public>
