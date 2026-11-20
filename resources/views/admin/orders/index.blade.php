<x-layouts.admin title="Orders">
    <x-admin.filter-bar :action="route('admin.orders.index')">
        <div class="lg:col-span-2"><label for="q" class="form-label">Search</label><input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Reference, name, email or phone" class="form-input"></div>
        <x-form.select name="status" label="Order status" :options="\App\Enums\OrderStatus::options()" :value="$filters['status'] ?? ''" placeholder="Any status" />
        <x-form.select name="payment" label="Payment" :options="\App\Enums\PaymentStatus::options()" :value="$filters['payment'] ?? ''" placeholder="Any" />
    </x-admin.filter-bar>
    @if ($orders->isEmpty())
        <x-empty-state title="No orders match" icon="cart" />
    @else
        <div class="card overflow-x-auto">
            <table class="table-admin">
                <caption class="sr-only">Orders</caption>
                <thead><tr><th scope="col">Order</th><th scope="col">Customer</th><th scope="col">Items</th><th scope="col">Total</th><th scope="col">Collection</th><th scope="col">Status</th></tr></thead>
                <tbody class="divide-y divide-navy/10 bg-white">
                    @foreach ($orders as $o)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $o) }}" class="font-semibold">{{ $o->reference }}</a><span class="block text-xs">{{ $o->created_at->format('j M Y, H:i') }}</span></td>
                            <td>{{ $o->customer_name }}<span class="block text-xs">{{ $o->customer_phone }}</span></td>
                            <td class="max-w-xs text-sm">{{ $o->shortSummary() }}</td>
                            <td class="whitespace-nowrap">{{ \App\Services\ShopService::money($o->total) }}</td>
                            <td class="text-sm">{{ $o->fulfilmentLabel() }}</td>
                            <td><div class="flex flex-col items-start gap-1"><x-status-badge :status="$o->status" size="sm" /><x-status-badge :status="$o->payment_status" size="sm" />@if ($o->stock_issue)<span class="text-xs font-semibold text-red-700">Stock issue</span>@endif</div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $orders->links() }}
    @endif
</x-layouts.admin>
