<x-layouts.public title="Your appointment request" :noindex="true">
    <section class="container-page max-w-xl py-12">
        <x-flash class="mb-6" />
        <p class="text-sm font-semibold uppercase tracking-wider text-teal-700">Appointment request</p>
        <h1 class="mt-1 font-display text-3xl font-bold">{{ $appointment->reference_code }}</h1>
        <p class="mt-2 text-sm">Keep this reference if you contact us about your request.</p>
        <dl class="mt-6 grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm">
            <dt class="font-semibold text-navy">Service</dt><dd>{{ $appointment->service?->name }}</dd>
            <dt class="font-semibold text-navy">Branch</dt><dd>{{ $appointment->branch?->name }}</dd>
            <dt class="font-semibold text-navy">Requested time</dt><dd>{{ $appointment->requestedStart()->format('l j F Y, H:i') }}</dd>
            <dt class="font-semibold text-navy">Status</dt><dd><x-status-badge :status="$appointment->status" size="sm" /></dd>
            @if ($appointment->confirmed_start_at)<dt class="font-semibold text-navy">Confirmed time</dt><dd class="font-semibold text-teal-700">{{ $appointment->confirmed_start_at->format('l j F Y, H:i') }}</dd>@endif
            @if ($appointment->consultation_fee)<dt class="font-semibold text-navy">Consultation fee</dt><dd>{{ \App\Services\ShopService::money($appointment->consultation_fee) }}</dd>@endif
            @if ($appointment->payment_status !== \App\Enums\PaymentStatus::NotRequired)<dt class="font-semibold text-navy">Deposit</dt><dd>{{ \App\Services\ShopService::money($appointment->amount_due) }} <x-status-badge :status="$appointment->payment_status" size="sm" /></dd>@endif
        </dl>

        @if ($appointment->canBePaid())
            <div class="card mt-8 space-y-3 p-6">
                <h2 class="text-lg font-semibold">Pay your deposit: {{ \App\Services\ShopService::money($appointment->amount_due) }}</h2>
                <p class="text-sm">Pay securely by card or mobile money on Paystack's page. Our team reviews requests once the deposit is paid.</p>
                <form method="POST" action="{{ URL::signedRoute('appointments.pay.start', $appointment) }}">@csrf<button class="btn-primary" type="submit"><x-icon name="card" class="h-4 w-4" /> Pay deposit with Paystack</button></form>
            </div>
        @elseif ($appointment->payment_status === \App\Enums\PaymentStatus::Paid)
            <x-alert type="success" title="Deposit paid" class="mt-8">
                We received {{ \App\Services\ShopService::money($appointment->amount_due) }} on {{ $appointment->paid_at?->format('j M Y, H:i') }}.
                @if ($appointment->balanceDue()) The balance of {{ \App\Services\ShopService::money($appointment->balanceDue()) }} is paid at the clinic. @endif
            </x-alert>
        @endif

        @if ($appointment->status->isOpen() && ! $appointment->confirmed_start_at)
            <x-alert type="warning" title="Your appointment is not yet confirmed" class="mt-6">
                Our team will confirm your time and let you know by {{ collect([$appointment->notify_sms ? 'SMS' : null, $appointment->notify_email ? 'email' : null])->filter()->join(' and ') ?: 'email' }}.
            </x-alert>
        @endif
        <p class="mt-8"><a href="{{ route('home') }}" class="btn-secondary">Back to home</a></p>
    </section>
</x-layouts.public>
