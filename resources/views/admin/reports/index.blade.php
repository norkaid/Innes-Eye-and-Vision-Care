<x-layouts.admin title="Reports">
    <form method="GET" action="{{ route('admin.reports.index') }}" class="card mb-6 flex flex-wrap items-end gap-3 p-4">
        <div><label for="from" class="form-label">From</label><input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="form-input"></div>
        <div><label for="to" class="form-label">To</label><input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="form-input"></div>
        <button class="btn-primary" type="submit">Update</button>
        @can('reports.export')
            <a href="{{ route('admin.reports.export', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="btn-secondary">Export summary (CSV)</a>
        @endcan
    </form>
    <p class="mb-6 text-sm">Figures are counted from records created between {{ $from->format('j M Y') }} and {{ $to->format('j M Y') }}. Exports contain totals only – no personal details.</p>
    <div class="grid gap-4 sm:grid-cols-2">
        <x-admin.stat-card label="Appointment requests" :value="$totalAppointments" icon="calendar" />
        <x-admin.stat-card label="Enquiries" :value="$totalEnquiries" icon="inbox" />
    </div>
    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        @foreach ([['Appointment outcomes (by current status)', $byStatus->map(fn ($v, $k) => (object) ['label' => $k, 'total' => $v])->values()], ['Requests by branch', $byBranch], ['Requests by service', $byService], ['Enquiries by status', $enquiriesByStatus->map(fn ($v, $k) => (object) ['label' => $k, 'total' => $v])->values()], ['Catalogue activity: product enquiries', $productEnquiries]] as [$heading, $rows])
            @php $max = max(1, collect($rows)->max('total')); @endphp
            <section class="card p-6">
                <h2 class="font-semibold">{{ $heading }}</h2>
                @if (collect($rows)->sum('total') === 0)
                    <p class="mt-3 text-sm">No records in this period.</p>
                @else
                    <table class="mt-3 w-full text-sm">
                        <thead class="sr-only"><tr><th>Item</th><th>Count</th></tr></thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <th scope="row" class="w-2/5 py-1.5 pr-3 text-left font-medium">{{ $row->label }}</th>
                                    <td class="py-1.5"><div class="flex items-center gap-2"><span class="block h-3 rounded bg-teal" style="width: {{ round($row->total / $max * 100) }}%" aria-hidden="true"></span><span class="font-semibold text-navy">{{ $row->total }}</span></div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        @endforeach
    </div>
</x-layouts.admin>
