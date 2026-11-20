<x-layouts.admin :title="$product->exists ? 'Edit product' : 'New product'">
    <x-slot:breadcrumb><a href="{{ route('admin.products.index') }}">Products</a></x-slot:breadcrumb>
    @if ($categories->isEmpty())
        <x-alert type="warning" title="Create a category first">Products must belong to a category. <a href="{{ route('admin.categories.create') }}">Create a category</a>.</x-alert>
    @else
    <form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-3" novalidate>
        @csrf
        @if ($product->exists) @method('PUT') @endif
        <div class="card space-y-5 p-6 xl:col-span-2">
            <x-form.input name="name" label="Product name" required :value="$product->name" />
            <x-form.input name="slug" label="URL slug" :value="$product->slug" hint="Leave blank to generate from the name." />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.select name="product_category_id" label="Category" required :options="$categories" :value="$product->product_category_id" placeholder="Choose a category" />
                <x-form.input name="brand" label="Brand" :value="$product->brand" />
            </div>
            <x-form.textarea name="short_description" label="Short description" rows="2" maxlength="300" :value="$product->short_description" />
            <x-form.textarea name="description" label="Full description" rows="6" :value="$product->description" />
            <x-admin.image-field :current="$product->image_path" folder="products" />
        </div>
        <div class="space-y-6">
            <div class="card space-y-4 p-6">
                <h2 class="font-semibold">Publishing</h2>
                <x-form.checkbox name="is_published" label="Published in the catalogue" :checked="(bool) $product->is_published" />
                <x-form.select name="availability_status" label="Availability label" required :options="\App\Enums\ProductAvailability::options()" :value="$product->availability_status" hint="Shown when the product is not sold online." />
                <x-form.input name="price" type="number" step="0.01" min="0" label="Price (GH₵)" :value="$product->price" hint="Leave blank unless the centre has approved a price. Required to sell online." />
            </div>
            <div class="card space-y-4 p-6">
                <h2 class="font-semibold">Online shop</h2>
                @if ($product->exists)
                    @php($blockers = $product->purchaseBlockers())
                    @if ($blockers)
                        <x-alert type="warning" title="Not on sale online yet">
                            <ul class="list-disc pl-5">@foreach ($blockers as $reason)<li>{{ $reason }}</li>@endforeach</ul>
                        </x-alert>
                    @else
                        <x-alert type="success">Customers can buy this product online.</x-alert>
                    @endif
                @endif
                <x-form.checkbox name="is_purchasable" label="Sell online (customers add to cart and pay)" :checked="(bool) $product->is_purchasable" hint="On by default: every published product with a price can be bought online. Untick only for items customers must ask about first." />
                <x-form.input name="stock_quantity" type="number" min="0" label="Stock available online" :value="$product->stock_quantity" hint="Leave blank if you do not track stock. When set, it goes down with each paid order and the product cannot be bought at 0." />
            </div>
            <div class="flex gap-3"><button class="btn-primary" type="submit">Save product</button><a href="{{ route('admin.products.index') }}" class="btn-secondary">Cancel</a></div>
        </div>
    </form>
    @endif
</x-layouts.admin>
