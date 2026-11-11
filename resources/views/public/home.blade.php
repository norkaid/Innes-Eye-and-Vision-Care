<x-layouts.public>
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-sky/70 to-white">
        <div class="container-page grid items-center gap-10 py-14 lg:grid-cols-2 lg:py-20">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-sm font-semibold text-teal-700 ring-1 ring-teal/30"><x-icon name="eye" class="h-4 w-4" /> Eye care &amp; optical services in Ghana</p>
                <h1 class="mt-5 font-display text-4xl font-bold leading-tight sm:text-5xl lg:text-6xl">Clear vision, cared for by people you can trust.</h1>
                <p class="mt-5 max-w-xl text-lg leading-8">{{ $site->get('business_name') }} offers eye examinations, vision care and a range of optical frames and lenses. Request an appointment online and our team will be in touch to confirm.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('appointments.create') }}" class="btn-primary px-6 py-3 text-base"><x-icon name="calendar" /> Book Appointment</a>
                    <a href="{{ route('products.index') }}" class="btn-secondary px-6 py-3 text-base"><x-icon name="glasses" /> View All Frames</a>
                </div>
                <p class="mt-4 text-sm">Online requests are reviewed by our team before they are confirmed.</p>
            </div>
            <div class="relative">
                {{-- Placeholder artwork. Replace with an approved, licensed photograph of the clinic or an eye examination. --}}
                <img src="{{ asset('images/hero-illustration.svg') }}" alt="Illustration of spectacles in front of an eye-test chart" class="w-full rounded-3xl shadow-xl" width="640" height="520" fetchpriority="high">
            </div>
        </div>
    </section>

    {{-- Intro --}}
    <section class="container-page py-16" aria-labelledby="intro-heading">
        <div class="grid gap-10 lg:grid-cols-3">
            <x-section-heading id="intro-heading" eyebrow="Welcome" title="Eye care and stylish vision solutions" class="lg:col-span-1" />
            <div class="space-y-4 text-lg leading-8 lg:col-span-2">
                @if ($site->get('about_intro'))
                    <p>{{ $site->get('about_intro') }}</p>
                @else
                    <p>We help patients look after their eye health and find eyewear that suits their needs and style. Explore our services, browse our optical catalogue and find the branch nearest to you.</p>
                    <p class="text-sm text-slate/90"><x-icon name="info" class="inline h-4 w-4" /> Introductory copy is a placeholder until approved wording is supplied by the centre.</p>
                @endif
                <a href="{{ route('about') }}" class="btn-text">More about us <x-icon name="arrow-right" class="h-4 w-4" /></a>
            </div>
        </div>
    </section>

    {{-- Services --}}
    <section class="bg-sky/50 py-16" aria-labelledby="services-heading">
        <div class="container-page">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <x-section-heading id="services-heading" eyebrow="Services" title="Our eye-care services" intro="From routine examinations to contact lens fitting – find out what each service involves." />
                <a href="{{ route('services.index') }}" class="btn-text">All services <x-icon name="arrow-right" class="h-4 w-4" /></a>
            </div>
            @if ($services->isNotEmpty())
                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services as $service)<x-service-card :service="$service" />@endforeach
                </div>
            @else
                <x-empty-state class="mt-10" title="Services coming soon" icon="eye">Service information will be published once confirmed by the centre.</x-empty-state>
            @endif
        </div>
    </section>

    {{-- Products --}}
    <section class="container-page py-16" aria-labelledby="products-heading">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <x-section-heading id="products-heading" eyebrow="Optical catalogue" title="Frames, sunglasses & lenses" intro="Browse a selection of our optical range and enquire about availability at your branch." />
            <a href="{{ route('products.index') }}" class="btn-secondary">View All Frames</a>
        </div>
        @if ($products->isNotEmpty())
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($products as $product)<x-product-card :product="$product" />@endforeach
            </div>
        @else
            <x-empty-state class="mt-10" title="Catalogue coming soon" icon="glasses" />
        @endif
    </section>

    {{-- Why choose us – only approved claims. --}}
    <section class="bg-sand py-16" aria-labelledby="why-heading">
        <div class="container-page">
            <x-section-heading id="why-heading" eyebrow="Why choose us" title="What you can expect when you visit" />
            <ul class="mt-10 grid gap-6 md:grid-cols-3">
                <li class="rounded-2xl bg-white p-6">
                    <x-icon name="calendar" class="h-8 w-8 text-teal-700" />
                    <h3 class="mt-3 text-lg font-semibold">Convenient online requests</h3>
                    <p class="mt-2 text-sm leading-6">Choose a branch, service and preferred time online. Our team reviews every request and contacts you to confirm.</p>
                </li>
                <li class="rounded-2xl bg-white p-6">
                    <x-icon name="glasses" class="h-8 w-8 text-teal-700" />
                    <h3 class="mt-3 text-lg font-semibold">Eye care and eyewear together</h3>
                    <p class="mt-2 text-sm leading-6">Find information on eye-care services and browse optical frames and lenses in one place.</p>
                </li>
                <li class="rounded-2xl bg-white p-6">
                    <x-icon name="shield" class="h-8 w-8 text-teal-700" />
                    <h3 class="mt-3 text-lg font-semibold">Your information protected</h3>
                    <p class="mt-2 text-sm leading-6">We only ask for the details needed to handle your request, and access is restricted to authorised staff.</p>
                </li>
            </ul>
            <p class="mt-6 text-sm"><x-icon name="info" class="inline h-4 w-4" /> Additional claims (experience, qualifications, accreditations) will be added only once verified and approved by the centre.</p>
        </div>
    </section>

    {{-- Branches --}}
    <section class="container-page py-16" aria-labelledby="branches-heading">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <x-section-heading id="branches-heading" eyebrow="Branches" title="Find a branch near you" />
            <a href="{{ route('branches.index') }}" class="btn-text">All branches <x-icon name="arrow-right" class="h-4 w-4" /></a>
        </div>
        @if ($branches->isNotEmpty())
            <div class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ($branches as $branch)<x-branch-card :branch="$branch" />@endforeach
            </div>
        @else
            <x-empty-state class="mt-10" title="Branch details coming soon" icon="map-pin" />
        @endif
    </section>

    {{-- Articles --}}
    @if ($articles->isNotEmpty())
        <section class="bg-navy-50 py-16" aria-labelledby="articles-heading">
            <div class="container-page">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <x-section-heading id="articles-heading" eyebrow="Eye health" title="Helpful eye-health advice" />
                    <a href="{{ route('articles.index') }}" class="btn-text">All articles <x-icon name="arrow-right" class="h-4 w-4" /></a>
                </div>
                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    @foreach ($articles as $article)<x-article-card :article="$article" />@endforeach
                </div>
            </div>
        </section>
    @endif

    <x-cta-band />
</x-layouts.public>
