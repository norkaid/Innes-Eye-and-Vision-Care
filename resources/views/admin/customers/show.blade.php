<x-layouts.admin :title="$customer->name">
    <x-slot:breadcrumb><a href="{{ route('admin.customers.index') }}">Customers</a></x-slot:breadcrumb>
    <x-slot:actions>
        @can('customers.manage')
            <a href="{{ route('admin.customers.edit', $customer) }}" class="btn-secondary btn-sm">Edit details</a>
            <x-admin.delete-button :action="route('admin.customers.toggle', $customer)" method="PATCH" :label="$customer->is_active ? 'Deactivate account' : 'Reactivate account'" :confirm="$customer->is_active ? 'Deactivate this account? The customer will be signed out and unable to sign in.' : 'Reactivate this account?'" :class="$customer->is_active ? 'btn-danger btn-sm' : 'btn-primary btn-sm'" />
        @endcan
    </x-slot:actions>
    <section class="card p-6">
        <dl class="grid gap-4 text-sm sm:grid-cols-4">
            <div><dt class="font-semibold text-navy">Email</dt><dd>{{ $customer->email }}</dd></div>
            <div><dt class="font-semibold text-navy">Phone</dt><dd>{{ $customer->phone ?: '—' }}</dd></div>
            <div><dt class="font-semibold text-navy">Account</dt><dd>{{ $customer->is_active ? 'Active' : 'Deactivated' }}</dd></div>
            <div><dt class="font-semibold text-navy">Last sign-in</dt><dd>{{ $customer->last_login_at?->format('j M Y, H:i') ?? '—' }}</dd></div>
        </dl>
    </section>
    <section class="card mt-6 overflow-x-auto" aria-labelledby="ah">
        <h2 id="ah" class="p-6 pb-0 text-lg font-semibold">Appointment history</h2>
        @if ($appointments->isEmpty())
            <p class="p-6 text-sm">No appointment requests.</p>
        @else
            <table class="table-admin mt-4">
                <thead><tr><th scope="col">Reference</th><th scope="col">Service</th><th scope="col">Branch</th><th scope="col">Requested</th><th scope="col">Status</th></tr></thead>
                <tbody class="divide-y divide-navy/10">
                    @foreach ($appointments as $a)
                        <tr>
                            <td>@can('appointments.view')<a href="{{ route('admin.appointments.show', $a) }}" class="font-mono">{{ $a->reference_code }}</a>@else<span class="font-mono">{{ $a->reference_code }}</span>@endcan</td>
                            <td>{{ $a->service->name }}</td><td>{{ $a->branch->name }}</td>
                            <td>{{ $a->requestedStart()->format('j M Y, H:i') }}</td>
                            <td><x-status-badge :status="$a->status" size="sm" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
    @if ($enquiries->isNotEmpty())
        <section class="card mt-6 p-6" aria-labelledby="eh">
            <h2 id="eh" class="text-lg font-semibold">Enquiries</h2>
            <ul class="mt-3 divide-y divide-navy/10">
                @foreach ($enquiries as $e)
                    <li class="flex justify-between py-2 text-sm"><a href="{{ route('admin.enquiries.show', $e) }}">{{ $e->subject }}</a><x-status-badge :status="$e->status" size="sm" /></li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.admin>
