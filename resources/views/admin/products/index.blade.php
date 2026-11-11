<x-layouts.admin title="Products">
    <x-slot:actions><a href="{{ route('admin.products.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> New product</a></x-slot:actions>
    <form method="GET" action="{{ route('admin.products.index') }}" role="search" class="card mb-4 flex flex-wrap items-end gap-3 p-4">
        @if ($archived)<input type="hidden" name="archived" value="1">@endif
        <div class="min-w-56 flex-1"><label for="q" class="form-label">Search</label><input id="q" name="q" type="search" value="{{ $q }}" placeholder="Name or brand" class="form-input"></div>
        <x-form.select name="category" label="Category" :options="$categories" :value="$category" placeholder="All categories" />
        <button class="btn-primary" type="submit">Filter</button>
    </form>
    <nav class="mb-4 flex gap-2 text-sm" aria-label="Product lists">
        <a href="{{ route('admin.products.index') }}" class="rounded-full px-3 py-1 font-semibold no-underline {{ ! $archived ? 'bg-navy text-white' : 'bg-white text-navy' }}">Current</a>
        <a href="{{ route('admin.products.index', ['archived' => 1]) }}" class="rounded-full px-3 py-1 font-semibold no-underline {{ $archived ? 'bg-navy text-white' : 'bg-white text-navy' }}">Archived</a>
    </nav>
    @if ($products->isEmpty())
        <x-empty-state title="No products" icon="glasses" />
    @else
        <div class="card overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th scope="col">Product</th><th scope="col">Category</th><th scope="col">Availability label</th><th scope="col">Price</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody class="divide-y divide-navy/10 bg-white">
                    @foreach ($products as $p)
                        <tr>
                            <td class="flex items-center gap-3">
                                <img src="{{ \App\Services\ImageUploader::url($p->image_path) ?? asset('images/placeholder-frame.svg') }}" alt="" class="h-10 w-14 rounded object-cover">
                                <span><span class="font-semibold text-navy">{{ $p->name }}</span><span class="block text-xs">{{ $p->brand }}</span></span>
                            </td>
                            <td>{{ $p->category?->name }}</td>
                            <td>{{ $p->availability_status->label() }}</td>
                            <td>{{ is_null($p->price) ? '—' : 'GH₵ '.number_format($p->price, 2) }}</td>
                            <td><x-admin.publish-badge :published="$p->is_published" :archived="$p->trashed()" /></td>
                            <td class="whitespace-nowrap text-right">
                                @if ($p->trashed())
                                    <x-admin.delete-button :action="route('admin.products.restore', $p)" method="PATCH" label="Restore" confirm="Restore this product as a draft?" class="btn-secondary btn-sm" />
                                @else
                                    <a href="{{ route('admin.products.edit', $p) }}" class="btn-secondary btn-sm">Edit</a>
                                    <x-admin.delete-button :action="route('admin.products.destroy', $p)" label="Archive" confirm="Archive this product? It will be unpublished." />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $products->links() }}
    @endif
</x-layouts.admin>
