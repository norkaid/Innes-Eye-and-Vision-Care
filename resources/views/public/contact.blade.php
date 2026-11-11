<x-layouts.public title="Contact us" description="Contact {{ $site->get('business_name') }} – branch phone numbers, email and an online enquiry form.">
    <x-page-header title="Contact us" eyebrow="Get in touch" :intro="$site->get('response_time_note') ?: 'Send us a message and a member of our team will respond as soon as possible.'" />

    <section class="container-page grid gap-10 py-12 lg:grid-cols-3">
        <div class="lg:col-span-2">
            @if (session('enquiry_sent'))
                <x-alert type="success" title="Message sent">
                    Thank you – your enquiry has been received and our team will respond.
                    @if (session('enquiry_acknowledged')) A confirmation has been sent to your email address. @endif
                </x-alert>
                <a href="{{ route('contact') }}" class="btn-secondary mt-6">Send another message</a>
            @else
                <form method="POST" action="{{ route('contact.store') }}" novalidate class="card relative space-y-5 p-6 sm:p-8">
                    @csrf
                    <x-form.honeypot />
                    <h2 class="text-xl font-semibold">Send an enquiry</h2>
                    <x-flash />
                    @if ($product)
                        <input type="hidden" name="product" value="{{ $product->slug }}">
                        <x-alert type="info" title="Product enquiry">You are asking about <strong>{{ $product->name }}</strong>.</x-alert>
                    @endif
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.input name="name" label="Your name" required autocomplete="name" :value="$user?->name" />
                        <x-form.input name="email" type="email" label="Email address" required autocomplete="email" :value="$user?->email" />
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.input name="phone" type="tel" label="Phone number" autocomplete="tel" :value="$user?->phone" />
                        <x-form.input name="subject" label="Subject" required :value="$product ? 'Product enquiry: '.$product->name : null" />
                    </div>
                    <x-form.textarea name="message" label="Message" required rows="6" maxlength="3000" hint="Please do not include detailed medical information." />
                    <x-form.checkbox name="privacy_consent" label="I agree to the centre using these details to respond to my enquiry." />
                    <p class="-mt-3 pl-7 text-sm">Read our <a href="{{ route('privacy') }}">privacy notice</a>.</p>
                    <button type="submit" class="btn-primary px-6 py-3">Send message</button>
                </form>
            @endif
        </div>

        <aside class="space-y-6">
            @if ($site->get('contact_phone') || $site->get('contact_email'))
                <div class="card p-6">
                    <h2 class="text-lg font-semibold">General enquiries</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        @if ($site->get('contact_phone'))<li class="flex gap-2"><x-icon name="phone" class="h-4 w-4 mt-0.5" /> <a href="tel:{{ preg_replace('/[^+\d]/', '', $site->get('contact_phone')) }}">{{ $site->get('contact_phone') }}</a></li>@endif
                        @if ($site->get('contact_email'))<li class="flex gap-2"><x-icon name="mail" class="h-4 w-4 mt-0.5" /> <a href="mailto:{{ $site->get('contact_email') }}">{{ $site->get('contact_email') }}</a></li>@endif
                    </ul>
                </div>
            @endif
            @foreach ($branches as $b)
                <div class="card p-6">
                    <h2 class="text-lg font-semibold"><a href="{{ route('branches.show', $b->slug) }}" class="text-navy">{{ $b->name }}</a></h2>
                    <div class="mt-2 space-y-1 text-sm">
                        @if ($b->address)<p>{{ $b->address }}</p>@endif
                        @if ($b->phone)<p><a href="tel:{{ preg_replace('/[^+\d]/', '', $b->phone) }}">{{ $b->phone }}</a></p>@endif
                        @if ($b->email)<p><a href="mailto:{{ $b->email }}">{{ $b->email }}</a></p>@endif
                    </div>
                </div>
            @endforeach
        </aside>
    </section>
</x-layouts.public>
