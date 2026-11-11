<x-layouts.admin title="Services">
    <x-slot:actions><a href="{{ route('admin.services.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> New service</a></x-slot:actions>
    <nav class="mb-4 flex gap-2 text-sm" aria-label="Service lists">
        <a href="{{ route('admin.services.index') }}" @if (! $archived) aria-current="page" @endif class="rounded-full px-3 py-1 font-semibold no-underline {{ ! $archived ? 'bg-navy text-white' : 'bg-white text-navy' }}">Current</a>
        <a href="{{ route('admin.services.index', ['archived' => 1]) }}" @if ($archived) aria-current="page" @endif class="rounded-full px-3 py-1 font-semibold no-underline {{ $archived ? 'bg-navy text-white' : 'bg-white text-navy' }}">Archived</a>
    </nav>
    @if ($services->isEmpty())
        <x-empty-state title="No services" icon="eye" />
    @else
        <div class="card overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th scope="col">Order</th><th scope="col">Name</th><th scope="col">Branches</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody class="divide-y divide-navy/10 bg-white">
                    @foreach ($services as $s)
                        <tr>
                            <td>{{ $s->display_order }}</td>
                            <td><span class="font-semibold text-navy">{{ $s->name }}</span><span class="block text-xs">/services/{{ $s->slug }}</span></td>
                            <td>{{ $s->branches_count }}</td>
                            <td><x-admin.publish-badge :published="$s->is_published" :archived="$s->trashed()" /></td>
                            <td class="whitespace-nowrap text-right">
                                @if ($s->trashed())
                                    <x-admin.delete-button :action="route('admin.services.restore', $s)" method="PATCH" label="Restore" confirm="Restore this service as a draft?" class="btn-secondary btn-sm" />
                                @else
                                    @if ($s->is_published)<a href="{{ route('services.show', $s->slug) }}" target="_blank" rel="noopener" class="btn-text text-sm">View</a>@endif
                                    <a href="{{ route('admin.services.edit', $s) }}" class="btn-secondary btn-sm">Edit</a>
                                    <x-admin.delete-button :action="route('admin.services.destroy', $s)" label="Archive" confirm="Archive this service? It will be unpublished." />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $services->links() }}
    @endif
</x-layouts.admin>
