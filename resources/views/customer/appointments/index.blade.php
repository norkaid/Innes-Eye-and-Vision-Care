<x-layouts.account title="Appointment requests">
    @if ($appointments->isEmpty())
        <x-empty-state title="No appointment requests yet" icon="calendar">Requests you make while signed in will appear here. <a href="{{ route('appointments.create') }}">Book an appointment</a>.</x-empty-state>
    @else
        <div class="card overflow-x-auto">
            <table class="table-admin">
                <caption class="sr-only">Your appointment requests</caption>
                <thead><tr><th scope="col">Reference</th><th scope="col">Service</th><th scope="col">Branch</th><th scope="col">Date &amp; time</th><th scope="col">Status</th></tr></thead>
                <tbody class="divide-y divide-navy/10">
                    @foreach ($appointments as $a)
                        <tr>
                            <td><a href="{{ route('account.appointments.show', $a) }}" class="font-mono font-semibold">{{ $a->reference_code }}</a></td>
                            <td>{{ $a->service->name }}</td>
                            <td>{{ $a->branch->name }}</td>
                            <td>{{ ($a->confirmed_start_at ?? $a->requestedStart())->format('D j M Y, H:i') }}@if (! $a->confirmed_start_at) <span class="block text-xs">(requested)</span>@endif</td>
                            <td><x-status-badge :status="$a->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $appointments->links() }}
    @endif
</x-layouts.account>
