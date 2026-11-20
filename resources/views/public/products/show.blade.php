<x-layouts.public :title="$product->name" :description="$product->short_description ?? $product->name.' – optical catalogue'" :image="\App\Services\ImageUploader::url($product->image_path)">
    <section class="container-page py-10">
        <x-flash class="mb-6" />
        <nav aria-label="Breadcrumb" class="text-sm"><x-breadcrumbs :items="['Frames & lenses' => route('products.index'), $product->category->name => route('products.index', ['category' => $product->category->slug]), $product->name => null]" /></nav>
        <div class="mt-8 grid gap-10 lg:grid-cols-2">
            <img src="{{ \App\Services\ImageUploader::url($product->image_path) ?? asset('images/placeholder-frame.svg') }}" alt="{{ $product->image_path ? $product->name : 'Placeholder image' }}" class="aspect-[4/3] w-full rounded-2xl bg-sand object-cover" width="800" height="600">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-teal-700">{{ $product->category->name }}</p>
                <h1 class="mt-1 font-display text-3xl font-bold sm:text-4xl">{{ $product->name }}</h1>
                <dl class="mt-6 grid grid-cols-[auto_1fr] gap-x-6 gap-y-3 text-sm">
                    @if ($product->brand)<dt class="font-semibold text-navy">Brand</dt><dd>{{ $product->brand }}</dd>@endif
                    <dt class="font-semibold text-navy">Availability</dt><dd><x-status-badge :status="$product->availability_status" /></dd>
                    @if (! is_null($product->price))<dt class="font-semibold text-navy">Price</dt><dd><span class="text-base font-semibold text-navy">GH₵ {{ number_format($product->price, 2) }}</span>@unless ($product->canBeBought()) <span class="text-slate">(confirm in branch)</span>@endunless</dd>@endif
                    @if ($product->canBeBought() && $product->stock_quantity !== null && $product->stock_quantity <= 5)<dt class="font-semibold text-navy">Stock</dt><dd>Only {{ $product->stock_quantity }} left</dd>@endif
                </dl>
                @if ($product->short_description)<p class="mt-6 text-lg leading-8">{{ $product->short_description }}</p>@endif
                @if ($product->description)<div class="prose-content mt-4">{!! nl2br(e($product->description)) !!}</div>@endif
                @if ($product->canBeBought())
                    <form method="POST" action="{{ route('shop.cart.add') }}" class="mt-8 flex flex-wrap items-end gap-3">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <div>
                            <label for="qty" class="form-label">Quantity</label>
                            <select id="qty" name="quantity" class="form-input w-24">
                                @for ($i = 1; $i <= $product->maxOrderQuantity(); $i++)<option value="{{ $i }}">{{ $i }}</option>@endfor
                            </select>
                        </div>
                        <button type="submit" class="btn-primary"><x-icon name="cart" class="h-4 w-4" /> Add to cart</button>
                    </form>
                    <p class="mt-2 text-sm">Pay securely online by card or mobile money, then {{ app(\App\Services\ShopService::class)->deliveryEnabled() ? 'collect in branch or have it delivered' : 'collect in branch' }}.</p>
                @endif
                <div class="mt-8 flex flex-wrap gap-3">
                    @unless ($product->canBeBought())
                        <a href="{{ route('contact', ['product' => $product->slug]) }}" class="btn-primary"><x-icon name="mail" class="h-4 w-4" /> Enquire About This Product</a>
                    @endunless
                    <a href="{{ route('appointments.create') }}" class="btn-secondary">Book an eye test</a>
                </div>
                @unless ($product->canBeBought())<p class="mt-4 text-sm">Availability is not guaranteed and may vary by branch. We will confirm when we reply to your enquiry.</p>@endunless
                @if ($product->category->slug === 'lenses' || str_contains(strtolower($product->category->name), 'lens'))<p class="mt-2 text-sm">Prescription lenses and contact lenses need a valid prescription; our team will contact you to confirm it before your order is prepared.</p>@endif
            </div>
        </div>
    </section>
    @if ($related->isNotEmpty())
        <section class="bg-sky/50 py-14" aria-labelledby="related-heading">
            <div class="container-page">
                <h2 id="related-heading" class="font-display text-2xl font-bold">More in {{ $product->category->name }}</h2>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($related as $p)<x-product-card :product="$p" />@endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.public>
