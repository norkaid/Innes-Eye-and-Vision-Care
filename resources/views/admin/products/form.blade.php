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
            <x-admin.image-field :current="$product->image_path" />
        </div>
        <div class="space-y-6">
            <div class="card space-y-4 p-6">
                <h2 class="font-semibold">Publishing</h2>
                <x-form.checkbox name="is_published" label="Published in the catalogue" :checked="(bool) $product->is_published" />
                <x-form.select name="availability_status" label="Availability label" required :options="\App\Enums\ProductAvailability::options()" :value="$product->availability_status" hint="A label only – the system does not track stock." />
                <x-form.input name="price" type="number" step="0.01" min="0" label="Price (GH₵)" :value="$product->price" hint="Leave blank unless the centre has approved a price for display." />
            </div>
            <div class="flex gap-3"><button class="btn-primary" type="submit">Save product</button><a href="{{ route('admin.products.index') }}" class="btn-secondary">Cancel</a></div>
        </div>
    </form>
    @endif
</x-layouts.admin>
