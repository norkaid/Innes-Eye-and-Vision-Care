@php
    $footerBranches = \App\Models\Branch::active()->ordered()->limit(4)->get(['name', 'slug', 'phone']);
@endphp
<footer class="mt-auto bg-navy text-white/85">
    <div class="container-page grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <x-logo light />
            <p class="mt-4 text-sm leading-6">{{ $site->get('tagline') }}.</p>
            <p class="mt-4 text-xs leading-5 text-white/70">Information on this website is for general guidance and is not a substitute for a professional eye examination.</p>
        </div>
        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Explore</h2>
            <ul class="mt-4 space-y-2 text-sm">
                <li><a class="text-white/85 no-underline hover:text-white hover:underline" href="{{ route('services.index') }}">Eye-care services</a></li>
                <li><a class="text-white/85 no-underline hover:text-white hover:underline" href="{{ route('products.index') }}">Frames &amp; lenses</a></li>
                <li><a class="text-white/85 no-underline hover:text-white hover:underline" href="{{ route('articles.index') }}">Eye-health resources</a></li>
                <li><a class="text-white/85 no-underline hover:text-white hover:underline" href="{{ route('about') }}">About us</a></li>
                <li><a class="text-white/85 no-underline hover:text-white hover:underline" href="{{ route('privacy') }}">Privacy notice</a></li>
            </ul>
        </div>
        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Branches</h2>
            <ul class="mt-4 space-y-2 text-sm">
                @forelse ($footerBranches as $b)
                    <li><a class="text-white/85 no-underline hover:text-white hover:underline" href="{{ route('branches.show', $b->slug) }}">{{ $b->name }}</a></li>
                @empty
                    <li>Branch details coming soon.</li>
                @endforelse
            </ul>
        </div>
        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Get in touch</h2>
            <ul class="mt-4 space-y-2 text-sm">
                @if ($site->get('contact_phone'))
                    <li class="flex gap-2"><x-icon name="phone" class="h-4 w-4 mt-0.5" /> <a class="text-white no-underline hover:underline" href="tel:{{ preg_replace('/[^+\d]/', '', $site->get('contact_phone')) }}">{{ $site->get('contact_phone') }}</a></li>
                @endif
                @if ($site->get('contact_email'))
                    <li class="flex gap-2"><x-icon name="mail" class="h-4 w-4 mt-0.5" /> <a class="text-white no-underline hover:underline" href="mailto:{{ $site->get('contact_email') }}">{{ $site->get('contact_email') }}</a></li>
                @endif
                <li><a class="text-white/85 no-underline hover:text-white hover:underline" href="{{ route('contact') }}">Send us a message</a></li>
            </ul>
            <a href="{{ route('appointments.create') }}" class="btn mt-5 bg-white text-navy hover:bg-sky">Book Appointment</a>
            @if ($site->get('facebook_url') || $site->get('instagram_url'))
                <div class="mt-5 flex gap-4 text-sm">
                    @if ($site->get('facebook_url'))<a class="text-white underline" href="{{ $site->get('facebook_url') }}" rel="noopener" target="_blank">Facebook</a>@endif
                    @if ($site->get('instagram_url'))<a class="text-white underline" href="{{ $site->get('instagram_url') }}" rel="noopener" target="_blank">Instagram</a>@endif
                </div>
            @endif
        </div>
    </div>
    <div class="border-t border-white/10">
        <p class="container-page py-5 text-xs text-white/70">&copy; {{ date('Y') }} {{ $site->get('business_name') }}, Ghana. All rights reserved.</p>
    </div>
</footer>
