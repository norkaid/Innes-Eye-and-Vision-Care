@props(['product'])
<article class="card group relative flex flex-col overflow-hidden transition-shadow hover:shadow-md">
    <img src="{{ \App\Services\ImageUploader::url($product->image_path) ?? asset('images/placeholder-frame.svg') }}" alt="{{ $product->image_path ? $product->name : '' }}" loading="lazy" class="aspect-[4/3] w-full bg-sand object-cover" width="400" height="300">
    <div class="flex flex-1 flex-col p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-teal-700">{{ $product->category?->name }}@if ($product->brand) · {{ $product->brand }}@endif</p>
        <h3 class="mt-1 text-base font-semibold">
            <a href="{{ route('products.show', $product->slug) }}" class="text-navy no-underline after:absolute after:inset-0 group-hover:underline">{{ $product->name }}</a>
        </h3>
        @if ($product->short_description)<p class="mt-2 line-clamp-2 flex-1 text-sm">{{ $product->short_description }}</p>@endif
        <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
            @if ($product->canBeBought())
                <span class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2 py-0.5 text-xs font-semibold text-teal-700 ring-1 ring-inset ring-teal/40"><x-icon name="cart" class="h-3.5 w-3.5" /> Buy online</span>
            @else
                <x-status-badge :status="$product->availability_status" size="sm" />
            @endif
            @if (! is_null($product->price))<span class="text-sm font-semibold text-navy">GH₵ {{ number_format($product->price, 2) }}</span>@endif
        </div>
    </div>
</article>
