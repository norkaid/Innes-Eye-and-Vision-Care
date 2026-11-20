<x-layouts.public :title="'Order '.$order->reference" :noindex="true">
    <section class="container-page max-w-3xl py-12">
        <x-flash class="mb-6" />
        <p class="text-sm font-semibold uppercase tracking-wider text-teal-700">Order</p>
        <h1 class="mt-1 font-display text-3xl font-bold">{{ $order->reference }}</h1>
        <div class="mt-3 flex flex-wrap gap-2"><x-status-badge :status="$order->status" /><x-status-badge :status="$order->payment_status" /></div>

        @if ($order->isAwaitingPayment())
            <div class="card mt-8 space-y-3 p-6">
                <h2 class="text-lg font-semibold">Payment needed: {{ \App\Services\ShopService::money($order->total) }}</h2>
                <p class="text-sm">Your order is saved but not paid yet. It will be prepared once payment is received.</p>
                <form method="POST" action="{{ URL::signedRoute('shop.orders.pay', $order) }}">@csrf<button class="btn-primary" type="submit"><x-icon name="card" class="h-4 w-4" /> Pay with Paystack</button></form>
            </div>
        @elseif ($order->payment_status === \App\Enums\PaymentStatus::Paid)
            <x-alert type="success" title="Payment received" class="mt-8">
                Thank you! We received {{ \App\Services\ShopService::money($order->total) }} on {{ $order->paid_at?->format('j M Y, H:i') }}.
                {{ $order->fulfilment === 'delivery' ? 'We will contact you to arrange delivery.' : 'We will let you know when your order is ready to collect.' }}
            </x-alert>
        @endif

        <div class="card mt-8 overflow-x-auto">
            <table class="table-admin">
                <caption class="sr-only">Items</caption>
                <thead><tr><th scope="col">Item</th><th scope="col">Qty</th><th scope="col" class="text-right">Total</th></tr></thead>
                <tbody class="divide-y divide-navy/10 bg-white">
                    @foreach ($order->items as $item)
                        <tr><td>{{ $item->product_name }}</td><td>{{ $item->quantity }}</td><td class="text-right">{{ \App\Services\ShopService::money($item->line_total) }}</td></tr>
                    @endforeach
                    @if ((float) $order->delivery_fee > 0)<tr><td>Delivery</td><td></td><td class="text-right">{{ \App\Services\ShopService::money($order->delivery_fee) }}</td></tr>@endif
                    <tr><th scope="row" class="text-left">Total</th><td></td><td class="text-right font-bold text-navy">{{ \App\Services\ShopService::money($order->total) }}</td></tr>
                </tbody>
            </table>
        </div>

        <dl class="mt-8 grid gap-4 text-sm sm:grid-cols-2">
            <div><dt class="font-semibold text-navy">Collection</dt><dd>{{ $order->fulfilmentLabel() }}</dd></div>
            <div><dt class="font-semibold text-navy">Placed</dt><dd>{{ $order->created_at->format('j M Y, H:i') }}</dd></div>
        </dl>
        <p class="mt-8 text-sm">Questions about your order? <a href="{{ route('contact') }}">Contact us</a> and quote <strong>{{ $order->reference }}</strong>.</p>
    </section>
</x-layouts.public>
