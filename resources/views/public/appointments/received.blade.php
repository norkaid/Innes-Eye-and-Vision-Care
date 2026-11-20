<x-layouts.public title="Appointment request received" :noindex="true">
    <section class="container-page max-w-2xl py-16 text-center">
        <span class="mx-auto inline-flex rounded-full bg-teal-50 p-4 text-teal-700"><x-icon name="check" class="h-10 w-10" /></span>
        <h1 class="mt-6 font-display text-3xl font-bold sm:text-4xl">Thank you – we have received your request</h1>
        @if ($reference)
            <div class="mx-auto mt-8 max-w-sm rounded-2xl border-2 border-dashed border-teal bg-sky/40 p-6">
                <p class="text-sm font-semibold uppercase tracking-wider">Your reference</p>
                <p class="mt-1 font-mono text-3xl font-bold tracking-widest text-navy">{{ $reference }}</p>
                <p class="mt-2 text-sm">Please keep this reference if you contact us about your request.</p>
            </div>
        @endif
        @if ($payment)
            <div class="card mx-auto mt-8 max-w-md space-y-3 p-6">
                <h2 class="text-lg font-semibold">Deposit to pay: {{ \App\Services\ShopService::money($payment['amount']) }}</h2>
                <p class="text-sm">Pay securely now by card or mobile money with Paystack.@if ($acknowledged) You can also pay later using the link in your email.@endif</p>
                <a href="{{ $payment['url'] }}" class="btn-primary w-full"><x-icon name="check" class="h-4 w-4" /> Pay deposit</a>
            </div>
        @endif
        <x-alert type="warning" title="Your appointment is not yet confirmed" class="mt-8 text-left">
            Our team will review your request and contact you to confirm a time or suggest an alternative.
        </x-alert>
        <p class="mt-6">
            @if ($acknowledged)
                An acknowledgement has been sent to the contact details you provided.
            @else
                We have not sent an acknowledgement message, so please note your reference above.
            @endif
        </p>
        <div class="mt-10 flex flex-wrap justify-center gap-3">
            <a href="{{ route('home') }}" class="btn-secondary">Back to home</a>
            @auth
                @if (auth()->user()->isCustomer())<a href="{{ route('account.appointments.index') }}" class="btn-primary">View my requests</a>@endif
            @else
                <a href="{{ route('products.index') }}" class="btn-primary">Browse frames</a>
            @endauth
        </div>
    </section>
</x-layouts.public>
