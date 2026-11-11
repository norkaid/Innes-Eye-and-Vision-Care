<x-layouts.admin title="Enquiries">
    <x-admin.filter-bar :action="route('admin.enquiries.index')">
        <div class="lg:col-span-2"><label for="q" class="form-label">Search</label><input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Name, email or subject" class="form-input"></div>
        <x-form.select name="status" label="Status" :options="\App\Enums\EnquiryStatus::options()" :value="$filters['status'] ?? ''" placeholder="Any status" />
        <x-form.select name="assigned" label="Assigned" :options="['me' => 'Assigned to me', 'none' => 'Unassigned']" :value="$filters['assigned'] ?? ''" placeholder="Anyone" />
    </x-admin.filter-bar>
    @if ($enquiries->isEmpty())
        <x-empty-state title="No enquiries match" icon="inbox" />
    @else
        <div class="card overflow-x-auto">
            <table class="table-admin">
                <caption class="sr-only">Enquiries</caption>
                <thead><tr><th scope="col">Subject</th><th scope="col">From</th><th scope="col">Received</th><th scope="col">Assigned to</th><th scope="col">Status</th></tr></thead>
                <tbody class="divide-y divide-navy/10 bg-white">
                    @foreach ($enquiries as $e)
                        <tr>
                            <td><a href="{{ route('admin.enquiries.show', $e) }}" class="font-semibold">{{ $e->subject }}</a>@if ($e->product)<span class="block text-xs">Product: {{ $e->product->name }}</span>@endif</td>
                            <td>{{ $e->name }}<span class="block text-xs">{{ $e->email }}</span></td>
                            <td>{{ $e->created_at->format('j M Y, H:i') }}</td>
                            <td>{{ $e->assignee?->name ?? '—' }}</td>
                            <td><x-status-badge :status="$e->status" size="sm" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $enquiries->links() }}
    @endif
</x-layouts.admin>
