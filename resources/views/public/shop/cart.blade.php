<x-layouts.public title="Your cart" :noindex="true">
    <x-page-header title="Your cart" eyebrow="Shop" />
    <section class="container-page py-12">
        <x-flash class="mb-6" />
        @if ($lines->isEmpty())
            <x-empty-state title="Your cart is empty" icon="cart">
                Browse our <a href="{{ route('products.index') }}">frames and lenses</a> and choose products marked “Buy online”.
            </x-empty-state>
        @else
            <div class="grid gap-8 lg:grid-cols-3">
                <form method="POST" action="{{ route('shop.cart.update') }}" class="card overflow-x-auto lg:col-span-2" id="cart-form">
                    @csrf @method('PATCH')
                    <table class="table-admin">
                        <caption class="sr-only">Items in your cart</caption>
                        <thead><tr><th scope="col">Product</th><th scope="col">Price</th><th scope="col">Quantity</th><th scope="col" class="text-right">Total</th><th scope="col"><span class="sr-only">Remove</span></th></tr></thead>
                        <tbody class="divide-y divide-navy/10 bg-white">
                            @foreach ($lines as $line)
                                @php($p = $line['product'])
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <img src="{{ \App\Services\ImageUploader::url($p->image_path) ?? asset('images/placeholder-frame.svg') }}" alt="" class="h-14 w-16 rounded bg-sand object-cover">
                                            <span><a href="{{ route('products.show', $p->slug) }}" class="font-semibold">{{ $p->name }}</a><span class="block text-xs">{{ $p->brand }}</span></span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">{{ \App\Services\ShopService::money($p->price) }}</td>
                                    <td>
                                        <label for="q-{{ $p->id }}" class="sr-only">Quantity of {{ $p->name }}</label>
                                        <select id="q-{{ $p->id }}" name="quantities[{{ $p->id }}]" class="form-input w-20" onchange="this.form.submit()">
                                            @for ($i = 0; $i <= $p->maxOrderQuantity(); $i++)<option value="{{ $i }}" @selected($i === $line['quantity'])>{{ $i === 0 ? 'Remove' : $i }}</option>@endfor
                                        </select>
                                    </td>
                                    <td class="whitespace-nowrap text-right font-semibold text-navy">{{ \App\Services\ShopService::money($line['line_total']) }}</td>
                                    <td class="text-right">
                                        <button type="submit" form="remove-{{ $p->id }}" class="btn-text text-sm">Remove</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <noscript><div class="p-4"><button class="btn-secondary btn-sm" type="submit">Update quantities</button></div></noscript>
                </form>
                @foreach ($lines as $line)
                    <form id="remove-{{ $line['product']->id }}" method="POST" action="{{ route('shop.cart.remove', $line['product']->id) }}" class="hidden">@csrf @method('DELETE')</form>
                @endforeach
                <aside class="card h-fit space-y-4 p-6">
                    <h2 class="text-lg font-semibold">Summary</h2>
                    <div class="flex justify-between"><span>Subtotal</span><strong class="text-navy">{{ \App\Services\ShopService::money($subtotal) }}</strong></div>
                    <p class="text-sm">Delivery or pickup is chosen at checkout.</p>
                    <a href="{{ route('shop.checkout') }}" class="btn-primary w-full">Go to checkout</a>
                    <a href="{{ route('products.index') }}" class="btn-secondary w-full">Continue shopping</a>
                </aside>
            </div>
        @endif
    </section>
</x-layouts.public>
