<x-layouts.admin title="Branches">
    <x-slot:actions><a href="{{ route('admin.branches.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> New branch</a></x-slot:actions>
    <nav class="mb-4 flex gap-2 text-sm" aria-label="Branch lists">
        <a href="{{ route('admin.branches.index') }}" class="rounded-full px-3 py-1 font-semibold no-underline {{ ! $archived ? 'bg-navy text-white' : 'bg-white text-navy' }}">Current</a>
        <a href="{{ route('admin.branches.index', ['archived' => 1]) }}" class="rounded-full px-3 py-1 font-semibold no-underline {{ $archived ? 'bg-navy text-white' : 'bg-white text-navy' }}">Archived</a>
    </nav>
    @if ($branches->isEmpty())
        <x-empty-state title="No branches" icon="building" />
    @else
        <div class="card overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th scope="col">Branch</th><th scope="col">Phone</th><th scope="col">Services</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody class="divide-y divide-navy/10 bg-white">
                    @foreach ($branches as $b)
                        <tr>
                            <td><span class="font-semibold text-navy">{{ $b->name }}</span><span class="block text-xs">{{ $b->city }}</span></td>
                            <td>{{ $b->phone ?: '—' }}</td>
                            <td>{{ $b->services_count }}</td>
                            <td><x-admin.publish-badge :published="$b->is_active" :archived="$b->trashed()" /></td>
                            <td class="whitespace-nowrap text-right">
                                @if ($b->trashed())
                                    <x-admin.delete-button :action="route('admin.branches.restore', $b)" method="PATCH" label="Restore" confirm="Restore this branch (inactive)?" class="btn-secondary btn-sm" />
                                @else
                                    <a href="{{ route('admin.branches.edit', $b) }}" class="btn-secondary btn-sm">Edit</a>
                                    <x-admin.delete-button :action="route('admin.branches.destroy', $b)" label="Archive" confirm="Archive this branch? It will disappear from the website and booking form." />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.admin>
