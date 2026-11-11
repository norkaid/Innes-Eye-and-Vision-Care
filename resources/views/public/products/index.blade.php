<x-layouts.public title="Frames, sunglasses & lenses" description="Browse optical frames, sunglasses, contact lenses and lenses at {{ $site->get('business_name') }}. Enquire about availability at your branch.">
    <x-page-header title="Optical catalogue" eyebrow="Frames & lenses" tone="sand" intro="Browse our optical range. Availability can vary by branch – send us an enquiry and we will let you know." />

    <section class="container-page py-12">
        <div class="grid gap-8 lg:grid-cols-[240px_1fr]">
            <aside aria-label="Product filters">
                <form method="GET" action="{{ route('products.index') }}" role="search" class="space-y-3">
                    @if ($category)<input type="hidden" name="category" value="{{ $category->slug }}">@endif
                    <label for="q" class="form-label">Search the catalogue</label>
                    <div class="flex gap-2">
                        <input id="q" name="q" type="search" value="{{ $q }}" placeholder="Name or brand" class="form-input">
                        <button class="btn-primary px-3" type="submit"><x-icon name="search" class="h-4 w-4" /><span class="sr-only">Search</span></button>
                    </div>
                </form>
                <h2 class="mt-8 text-sm font-semibold uppercase tracking-wider">Categories</h2>
                <ul class="mt-3 space-y-1">
                    <li><a href="{{ route('products.index', array_filter(['q' => $q])) }}" @if (! $category) aria-current="true" @endif class="block rounded-md px-3 py-2 text-sm font-semibold no-underline {{ ! $category ? 'bg-navy text-white' : 'text-navy hover:bg-sky' }}">All products</a></li>
                    @foreach ($categories as $c)
                        <li><a href="{{ route('products.index', array_filter(['category' => $c->slug, 'q' => $q])) }}" @if ($category?->id === $c->id) aria-current="true" @endif class="flex justify-between rounded-md px-3 py-2 text-sm font-semibold no-underline {{ $category?->id === $c->id ? 'bg-navy text-white' : 'text-navy hover:bg-sky' }}">{{ $c->name }} <span class="font-normal">{{ $c->products_count }}</span></a></li>
                    @endforeach
                </ul>
            </aside>

            <div aria-live="polite">
                <p class="mb-6 text-sm">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('item', $products->total()) }}@if ($category) in <strong>{{ $category->name }}</strong>@endif @if ($q) matching “<strong>{{ $q }}</strong>”@endif @if ($q || $category) · <a href="{{ route('products.index') }}">Clear filters</a>@endif</p>
                @if ($products->isNotEmpty())
                    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($products as $product)<x-product-card :product="$product" />@endforeach
                    </div>
                    {{ $products->links() }}
                @else
                    <x-empty-state title="No products found" icon="glasses">Try another search or category.</x-empty-state>
                @endif
                <p class="mt-10 text-sm"><x-icon name="info" class="inline h-4 w-4" /> This catalogue is for browsing and enquiries only – there is no online checkout. Prices, where shown, are indicative and confirmed in branch.</p>
            </div>
        </div>
    </section>
</x-layouts.public>
