<x-layouts.account title="Welcome, {{ $user->first_name }}">
    <div class="grid gap-6 md:grid-cols-2">
        <div class="card p-6">
            <h2 class="text-lg font-semibold">Need an appointment?</h2>
            <p class="mt-2 text-sm">Requests made while signed in appear in your account.</p>
            <a href="{{ route('appointments.create') }}" class="btn-primary mt-4">Book Appointment</a>
        </div>
        <div class="card p-6">
            <h2 class="text-lg font-semibold">Your details</h2>
            <p class="mt-2 text-sm">{{ $user->email }}<br>{{ $user->phone ?: 'No phone number saved' }}</p>
            <a href="{{ route('account.profile') }}" class="btn-text mt-4 text-sm">Update details</a>
        </div>
    </div>
    <div class="card p-6">
        <h2 class="text-lg font-semibold">Open requests</h2>
        @if ($upcoming->isEmpty())
            <p class="mt-2 text-sm">You have no open appointment requests.</p>
        @else
            <ul class="mt-3 divide-y divide-navy/10">
                @foreach ($upcoming as $a)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div>
                            <a href="{{ route('account.appointments.show', $a) }}" class="font-semibold">{{ $a->service->name }}</a>
                            <p class="text-sm">{{ $a->branch->name }} · {{ ($a->confirmed_start_at ?? $a->requestedStart())->format('D j M Y, H:i') }}</p>
                        </div>
                        <x-status-badge :status="$a->status" />
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.account>
