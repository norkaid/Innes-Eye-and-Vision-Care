<x-layouts.admin title="Customers">
    <form method="GET" action="{{ route('admin.customers.index') }}" role="search" class="card mb-6 flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-64 flex-1"><label for="q" class="form-label">Search customers</label><input id="q" name="q" type="search" value="{{ $q }}" placeholder="Name, email or phone" class="form-input"></div>
        <button class="btn-primary" type="submit">Search</button>
    </form>
    <p class="mb-4 text-sm">Registered customer accounts. Guest appointment requests are listed under Appointments.</p>
    @if ($customers->isEmpty())
        <x-empty-state title="No customer accounts found" icon="users" />
    @else
        <div class="card overflow-x-auto">
            <table class="table-admin">
                <caption class="sr-only">Customer accounts</caption>
                <thead><tr><th scope="col">Name</th><th scope="col">Contact</th><th scope="col">Requests</th><th scope="col">Joined</th><th scope="col">Account</th></tr></thead>
                <tbody class="divide-y divide-navy/10 bg-white">
                    @foreach ($customers as $c)
                        <tr>
                            <td><a href="{{ route('admin.customers.show', $c) }}" class="font-semibold">{{ $c->name }}</a></td>
                            <td>{{ $c->email }}<span class="block text-xs">{{ $c->phone }}</span></td>
                            <td>{{ $c->appointments_count }}</td>
                            <td>{{ $c->created_at->format('j M Y') }}</td>
                            <td>{{ $c->is_active ? 'Active' : 'Deactivated' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $customers->links() }}
    @endif
</x-layouts.admin>
