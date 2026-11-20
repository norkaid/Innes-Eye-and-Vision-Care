<x-layouts.admin title="Payments">
    @if (! $configured)
        <x-alert type="warning" title="Paystack is not set up" class="mb-6">Add <code>PAYSTACK_SECRET_KEY</code> (and <code>PAYSTACK_PUBLIC_KEY</code>) to the <code>.env</code> file, then run <code>php artisan config:clear</code>. Until then the shop and booking-fee payments are hidden from customers.</x-alert>
    @elseif ($testMode)
        <x-alert type="info" title="Test mode" class="mb-6">Paystack test keys are in use: no real money is taken. Switch to live keys (<code>sk_live_…</code>) before going live.</x-alert>
    @endif
    <x-admin.filter-bar :action="route('admin.payments.index')">
        <div class="lg:col-span-2"><label for="q" class="form-label">Search</label><input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Payment reference or email" class="form-input"></div>
        <x-form.select name="status" label="Result" :options="['success' => 'Successful', 'pending' => 'Started', 'failed' => 'Failed', 'abandoned' => 'Not completed']" :value="$filters['status'] ?? ''" placeholder="Any" />
        <x-form.select name="type" label="For" :options="['order' => 'Shop orders', 'appointment' => 'Appointment fees']" :value="$filters['type'] ?? ''" placeholder="Everything" />
        <x-form.input name="from" type="date" label="From" :value="$filters['from'] ?? ''" />
        <x-form.input name="to" type="date" label="To" :value="$filters['to'] ?? ''" />
    </x-admin.filter-bar>
    <p class="mb-4 text-sm">Received in the selected period: <strong class="text-navy">{{ \App\Services\ShopService::money($received) }}</strong> (successful payments only; refunds are made and shown in the Paystack dashboard).</p>
    @if ($payments->isEmpty())
        <x-empty-state title="No payments yet" icon="card" />
    @else
        <div class="card overflow-x-auto">
            <table class="table-admin">
                <caption class="sr-only">Payments</caption>
                <thead><tr><th scope="col">Reference</th><th scope="col">For</th><th scope="col">Amount</th><th scope="col">Result</th><th scope="col">Method</th><th scope="col">Date</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody class="divide-y divide-navy/10 bg-white">
                    @foreach ($payments as $p)
                        <tr>
                            <td class="font-mono text-xs">{{ $p->reference }}<span class="block font-sans">{{ $p->customer_email }}</span></td>
                            <td>
                                @if ($p->payable instanceof \App\Models\Order)
                                    @can('orders.view')<a href="{{ route('admin.orders.show', $p->payable) }}">Order {{ $p->payable->reference }}</a>@else Order {{ $p->payable->reference }} @endcan
                                @elseif ($p->payable instanceof \App\Models\Appointment)
                                    @can('appointments.view')<a href="{{ route('admin.appointments.show', $p->payable) }}">Appointment {{ $p->payable->reference_code }}</a>@else Appointment {{ $p->payable->reference_code }} @endcan
                                @else — @endif
                            </td>
                            <td class="whitespace-nowrap">{{ \App\Services\ShopService::money($p->amount()) }}</td>
                            <td>{{ $p->statusLabel() }}@if ($p->gateway_response && $p->status !== 'success')<span class="block text-xs">{{ $p->gateway_response }}</span>@endif</td>
                            <td>{{ $p->channelLabel() ?? '—' }}</td>
                            <td class="whitespace-nowrap">{{ $p->created_at->format('j M Y, H:i') }}</td>
                            <td class="text-right">
                                @if ($p->status === 'pending' && $configured)
                                    <x-admin.delete-button :action="route('admin.payments.recheck', $p)" method="POST" label="Check again" confirm="Ask Paystack for the latest result of this payment?" class="btn-secondary btn-sm" />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $payments->links() }}
    @endif
</x-layouts.admin>
