<x-layouts.admin title="Appointment requests">
    <x-admin.filter-bar :action="route('admin.appointments.index')">
        <div class="lg:col-span-2"><label for="q" class="form-label">Search</label><input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Reference, name, email or phone" class="form-input"></div>
        <x-form.select name="status" label="Status" :options="\App\Enums\AppointmentStatus::options()" :value="$filters['status'] ?? ''" placeholder="Any status" />
        <x-form.select name="branch" label="Branch" :options="$branches" :value="$filters['branch'] ?? ''" placeholder="Any branch" />
        <x-form.select name="service" label="Service" :options="$services" :value="$filters['service'] ?? ''" placeholder="Any service" />
        <div class="grid grid-cols-2 gap-2 lg:col-span-1">
            <div><label for="from" class="form-label">From</label><input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-input px-2"></div>
            <div><label for="to" class="form-label">To</label><input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-input px-2"></div>
        </div>
    </x-admin.filter-bar>

    @if ($appointments->isEmpty())
        <x-empty-state title="No appointment requests match" icon="calendar" />
    @else
        <div class="card overflow-x-auto">
            <table class="table-admin">
                <caption class="sr-only">Appointment requests</caption>
                <thead><tr><th scope="col">Reference</th><th scope="col">Customer</th><th scope="col">Service &amp; branch</th><th scope="col">Requested</th><th scope="col">Confirmed</th><th scope="col">Status</th></tr></thead>
                <tbody class="divide-y divide-navy/10 bg-white">
                    @foreach ($appointments as $a)
                        <tr>
                            <td><a href="{{ route('admin.appointments.show', $a) }}" class="font-mono font-semibold">{{ $a->reference_code }}</a><span class="block text-xs">{{ $a->created_at->format('j M, H:i') }}</span></td>
                            <td><span class="font-semibold text-navy">{{ $a->customer_name }}</span><span class="block text-xs">{{ $a->customer_phone }}</span></td>
                            <td>{{ $a->service->name }}<span class="block text-xs">{{ $a->branch->name }}</span></td>
                            <td>{{ $a->requestedStart()->format('D j M Y, H:i') }}</td>
                            <td>{{ $a->confirmed_start_at?->format('D j M, H:i') ?? '—' }}</td>
                            <td><x-status-badge :status="$a->status" size="sm" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $appointments->links() }}
    @endif
</x-layouts.admin>
