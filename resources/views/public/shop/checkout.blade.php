<x-layouts.public title="Checkout" :noindex="true">
    <x-page-header title="Checkout" eyebrow="Shop" intro="Enter your details, choose pickup or delivery, then pay securely with Paystack." />
    <section class="container-page py-12" x-data="{ fulfilment: @js(old('fulfilment', array_key_first($options))) }">
        <x-flash class="mb-6" />
        @if (! $options)
            <x-alert type="warning" title="Ordering is paused">Pickup and delivery are both switched off at the moment. Please contact us to order.</x-alert>
        @else
            <form method="POST" action="{{ route('shop.checkout.store') }}" class="grid gap-8 lg:grid-cols-3" novalidate>
                @csrf
                <x-form.honeypot />
                <div class="space-y-6 lg:col-span-2">
                    <fieldset class="card space-y-5 p-6">
                        <legend class="sr-only">Your details</legend>
                        <h2 class="text-lg font-semibold" aria-hidden="true">Your details</h2>
                        <x-form.input name="customer_name" label="Full name" required autocomplete="name" :value="$prefill['customer_name'] ?? null" />
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form.input name="customer_email" type="email" label="Email address" required autocomplete="email" :value="$prefill['customer_email'] ?? null" hint="Your receipt and order updates are sent here." />
                            <x-form.input name="customer_phone" type="tel" label="Phone number" required autocomplete="tel" :value="$prefill['customer_phone'] ?? null" />
                        </div>
                    </fieldset>

                    <fieldset class="card space-y-4 p-6">
                        <legend class="sr-only">Pickup or delivery</legend>
                        <h2 class="text-lg font-semibold" aria-hidden="true">Pickup or delivery</h2>
                        @foreach ($options as $value => $label)
                            <label class="flex items-center gap-3 rounded-lg border border-navy/15 p-3 has-[:checked]:border-teal has-[:checked]:bg-teal-50">
                                <input type="radio" name="fulfilment" value="{{ $value }}" x-model="fulfilment" class="h-4 w-4 text-teal" @checked(old('fulfilment', array_key_first($options)) === $value)>
                                <span class="font-semibold text-navy">{{ $label }}</span>
                            </label>
                        @endforeach
                        @error('fulfilment')<p class="form-error">{{ $message }}</p>@enderror
                        <div x-show="fulfilment === 'pickup'">
                            <x-form.select name="pickup_branch_id" label="Collect from" required :options="$branches->pluck('name', 'id')->all()" placeholder="Choose a branch" />
                        </div>
                        <div x-show="fulfilment === 'delivery'" x-cloak>
                            <x-form.textarea name="delivery_address" label="Delivery address" required rows="3" hint="House number, street, area, city and a landmark." />
                            @if ($site->get('shop_delivery_note'))<p class="form-hint mt-2">{{ $site->get('shop_delivery_note') }}</p>@endif
                        </div>
                    </fieldset>

                    <div class="card space-y-4 p-6">
                        <x-form.textarea name="customer_note" label="Note for our team" rows="2" hint="e.g. preferred pickup time. Please do not include medical details." />
                        <x-form.checkbox name="accept_terms" label="I confirm my details are correct. Prescription items will be checked with me before they are prepared." />
                    </div>
                </div>

                <aside class="card h-fit space-y-4 p-6">
                    <h2 class="text-lg font-semibold">Your order</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach ($lines as $line)
                            <li class="flex justify-between gap-3"><span>{{ $line['quantity'] }} × {{ $line['product']->name }}</span><span class="whitespace-nowrap">{{ \App\Services\ShopService::money($line['line_total']) }}</span></li>
                        @endforeach
                    </ul>
                    <div class="space-y-1 border-t border-navy/10 pt-3 text-sm">
                        <div class="flex justify-between"><span>Subtotal</span><span>{{ \App\Services\ShopService::money($subtotal) }}</span></div>
                        <div class="flex justify-between" x-show="fulfilment === 'delivery'" x-cloak><span>Delivery</span><span>{{ \App\Services\ShopService::money($deliveryFee) }}</span></div>
                        <div class="flex justify-between text-base font-bold text-navy"><span>Total</span>
                            <span x-text="fulfilment === 'delivery' ? @js(\App\Services\ShopService::money($subtotal + $deliveryFee)) : @js(\App\Services\ShopService::money($subtotal))">{{ \App\Services\ShopService::money($subtotal) }}</span>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary w-full"><x-icon name="card" class="h-4 w-4" /> Pay with Paystack</button>
                    <p class="text-xs">You will be taken to Paystack's secure page to pay by card or mobile money. We never see or store your card details.</p>
                </aside>
            </form>
        @endif
    </section>
</x-layouts.public>
