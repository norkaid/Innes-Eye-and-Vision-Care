<x-layouts.account title="My orders">
    @forelse ($orders as $order)
        <article class="card p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold"><a href="{{ $order->publicUrl() }}">{{ $order->reference }}</a></h2>
                    <p class="text-sm">Placed {{ $order->created_at->format('j M Y, H:i') }} · {{ \App\Services\ShopService::money($order->total) }} · {{ $order->fulfilmentLabel() }}</p>
                </div>
                <div class="flex flex-wrap gap-2"><x-status-badge :status="$order->status" /><x-status-badge :status="$order->payment_status" /></div>
            </div>
            <p class="mt-3 text-sm">{{ $order->shortSummary() }}</p>
        </article>
    @empty
        <x-empty-state title="No orders yet" icon="cart">Orders you place while signed in will appear here. <a href="{{ route('products.index') }}">Browse frames and lenses</a>.</x-empty-state>
    @endforelse
    {{ $orders->links() }}
</x-layouts.account>
