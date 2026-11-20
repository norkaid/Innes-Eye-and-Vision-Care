<x-layouts.public title="Pay booking fee" :noindex="true">
    <section class="container-page max-w-xl py-12">
        <x-flash class="mb-6" />
        <p class="text-sm font-semibold uppercase tracking-wider text-teal-700">Appointment request</p>
        <h1 class="mt-1 font-display text-3xl font-bold">{{ $appointment->reference_code }}</h1>
        <dl class="mt-6 grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm">
            <dt class="font-semibold text-navy">Service</dt><dd>{{ $appointment->service?->name }}</dd>
            <dt class="font-semibold text-navy">Branch</dt><dd>{{ $appointment->branch?->name }}</dd>
            <dt class="font-semibold text-navy">Request status</dt><dd><x-status-badge :status="$appointment->status" size="sm" /></dd>
            <dt class="font-semibold text-navy">Payment</dt><dd><x-status-badge :status="$appointment->payment_status" size="sm" /></dd>
        </dl>

        @if ($appointment->canBePaid())
            <div class="card mt-8 space-y-3 p-6">
                <h2 class="text-lg font-semibold">Booking fee: {{ \App\Services\ShopService::money($appointment->amount_due) }}</h2>
                <p class="text-sm">Pay securely by card or mobile money on Paystack's page. Paying does not confirm the appointment: our team will still contact you to confirm a time.</p>
                <form method="POST" action="{{ URL::signedRoute('appointments.pay.start', $appointment) }}">@csrf<button class="btn-primary" type="submit"><x-icon name="card" class="h-4 w-4" /> Pay with Paystack</button></form>
            </div>
        @elseif ($appointment->payment_status === \App\Enums\PaymentStatus::Paid)
            <x-alert type="success" title="Booking fee paid" class="mt-8">
                We received {{ \App\Services\ShopService::money($appointment->amount_due) }} on {{ $appointment->paid_at?->format('j M Y, H:i') }}. Our team will contact you to confirm your appointment.
            </x-alert>
        @else
            <x-alert type="info" class="mt-8">No payment is due for this request.</x-alert>
        @endif
    </section>
</x-layouts.public>
