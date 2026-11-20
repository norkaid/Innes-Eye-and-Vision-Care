<x-layouts.admin :title="'Order '.$order->reference">
    <x-slot:breadcrumb><a href="{{ route('admin.orders.index') }}">Orders</a></x-slot:breadcrumb>
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="card p-6">
                <div class="flex flex-wrap items-center gap-2"><x-status-badge :status="$order->status" /><x-status-badge :status="$order->payment_status" /></div>
                @if ($order->stock_issue)
                    <x-alert type="error" title="Stock issue" class="mt-4">When this order was paid, there was less stock than ordered. Check availability and contact the customer.</x-alert>
                @endif
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="font-semibold text-navy">Customer</dt><dd>{{ $order->customer_name }}@if ($order->user) <span class="text-xs">(registered account)</span>@endif</dd></div>
                    <div><dt class="font-semibold text-navy">Contact</dt><dd><a href="mailto:{{ $order->customer_email }}">{{ $order->customer_email }}</a><br><a href="tel:{{ preg_replace('/[^0-9+]/', '', $order->customer_phone) }}">{{ $order->customer_phone }}</a></dd></div>
                    <div><dt class="font-semibold text-navy">Collection</dt><dd>{{ $order->fulfilmentLabel() }}</dd></div>
                    @if ($order->delivery_address)<div><dt class="font-semibold text-navy">Delivery address</dt><dd class="whitespace-pre-line">{{ $order->delivery_address }}</dd></div>@endif
                    <div><dt class="font-semibold text-navy">Placed</dt><dd>{{ $order->created_at->format('j M Y, H:i') }}</dd></div>
                    <div><dt class="font-semibold text-navy">Paid</dt><dd>{{ $order->paid_at?->format('j M Y, H:i') ?? '—' }}</dd></div>
                    @if ($order->customer_note)<div class="sm:col-span-2"><dt class="font-semibold text-navy">Customer note</dt><dd class="whitespace-pre-line">{{ $order->customer_note }}</dd></div>@endif
                </dl>
            </div>
            <div class="card overflow-x-auto">
                <table class="table-admin">
                    <caption class="px-4 pt-4 text-left font-semibold text-navy">Items</caption>
                    <thead><tr><th scope="col">Product</th><th scope="col">Unit price</th><th scope="col">Qty</th><th scope="col" class="text-right">Total</th></tr></thead>
                    <tbody class="divide-y divide-navy/10 bg-white">
                        @foreach ($order->items as $item)
                            <tr>
                                <td>@if ($item->product)<a href="{{ route('admin.products.edit', $item->product) }}">{{ $item->product_name }}</a>@else{{ $item->product_name }}@endif</td>
                                <td>{{ \App\Services\ShopService::money($item->unit_price) }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td class="text-right">{{ \App\Services\ShopService::money($item->line_total) }}</td>
                            </tr>
                        @endforeach
                        @if ((float) $order->delivery_fee > 0)<tr><td colspan="3">Delivery</td><td class="text-right">{{ \App\Services\ShopService::money($order->delivery_fee) }}</td></tr>@endif
                        <tr><th scope="row" colspan="3" class="text-left">Total</th><td class="text-right font-bold text-navy">{{ \App\Services\ShopService::money($order->total) }}</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="card overflow-x-auto">
                <table class="table-admin">
                    <caption class="px-4 pt-4 text-left font-semibold text-navy">Payment attempts</caption>
                    <thead><tr><th scope="col">Reference</th><th scope="col">Amount</th><th scope="col">Result</th><th scope="col">Method</th><th scope="col">Date</th></tr></thead>
                    <tbody class="divide-y divide-navy/10 bg-white">
                        @forelse ($order->payments as $p)
                            <tr><td class="font-mono text-xs">{{ $p->reference }}</td><td>{{ \App\Services\ShopService::money($p->amount()) }}</td><td>{{ $p->statusLabel() }}</td><td>{{ $p->channelLabel() ?? '—' }}</td><td>{{ $p->created_at->format('j M Y, H:i') }}</td></tr>
                        @empty
                            <tr><td colspan="5">No payment attempts yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="space-y-6">
            @can('orders.manage')
                @php($next = $order->status->staffTransitions($order->fulfilment))
                @if ($next)
                    <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="card space-y-4 p-6">
                        @csrf @method('PATCH')
                        <h2 class="font-semibold">Update order</h2>
                        <x-form.select name="status" label="New status" required :options="collect($next)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" />
                        <x-form.checkbox name="notify" label="Notify the customer (email, SMS and their account)" :checked="true" />
                        @if ($order->payment_status === \App\Enums\PaymentStatus::Paid)<p class="text-xs">Cancelling a paid order returns its items to stock. Refund the customer in the Paystack dashboard.</p>@endif
                        <button class="btn-primary" type="submit">Save</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('admin.orders.note', $order) }}" class="card space-y-4 p-6">
                    @csrf @method('PATCH')
                    <x-form.textarea name="staff_note" label="Internal note (staff only)" rows="4" :value="$order->staff_note" />
                    <button class="btn-secondary" type="submit">Save note</button>
                </form>
                @if ($order->payment_status === \App\Enums\PaymentStatus::Paid && $order->status === \App\Enums\OrderStatus::Cancelled)
                    <div class="card space-y-3 p-6">
                        <h2 class="font-semibold">Refund</h2>
                        <p class="text-sm">Refund {{ \App\Services\ShopService::money($order->total) }} in the Paystack dashboard (Transactions → this payment → Refund), then record it here.</p>
                        <x-admin.delete-button :action="route('admin.orders.refund', $order)" method="POST" label="Record refund" confirm="Have you refunded this payment in Paystack?" class="btn-secondary" />
                    </div>
                @endif
            @endcan
            @if ($order->handler)<p class="text-sm">Last updated by {{ $order->handler->name }}.</p>@endif
        </div>
    </div>
</x-layouts.admin>
