<x-layouts.admin title="Product categories">
    <x-slot:actions><a href="{{ route('admin.categories.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> New category</a></x-slot:actions>
    @if ($categories->isEmpty())
        <x-empty-state title="No categories" icon="tag" />
    @else
        <div class="card overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th scope="col">Order</th><th scope="col">Name</th><th scope="col">Products</th><th scope="col">Visible</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody class="divide-y divide-navy/10 bg-white">
                    @foreach ($categories as $c)
                        <tr>
                            <td>{{ $c->display_order }}</td>
                            <td class="font-semibold text-navy">{{ $c->name }}</td>
                            <td>{{ $c->products_count }}</td>
                            <td>{{ $c->is_active ? 'Yes' : 'No (hidden)' }}</td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.categories.edit', $c) }}" class="btn-secondary btn-sm">Edit</a>
                                <x-admin.delete-button :action="route('admin.categories.destroy', $c)" confirm="Delete this category?" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.admin>
