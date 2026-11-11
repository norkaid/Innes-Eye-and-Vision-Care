<x-layouts.admin title="Health articles">
    <x-slot:actions><a href="{{ route('admin.articles.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> New article</a></x-slot:actions>
    <div class="grid gap-6 xl:grid-cols-[1fr_300px]">
        <div>
            <nav class="mb-4 flex flex-wrap gap-2 text-sm" aria-label="Article status">
                @foreach (['' => 'All', 'draft' => 'Drafts', 'published' => 'Published', 'archived' => 'Archived', 'trashed' => 'Bin'] as $key => $label)
                    <a href="{{ route('admin.articles.index', array_filter(['status' => $key])) }}" class="rounded-full px-3 py-1 font-semibold no-underline {{ $status === $key ? 'bg-navy text-white' : 'bg-white text-navy' }}">{{ $label }}</a>
                @endforeach
            </nav>
            @if ($articles->isEmpty())
                <x-empty-state title="No articles" icon="book" />
            @else
                <div class="card overflow-x-auto">
                    <table class="table-admin">
                        <thead><tr><th scope="col">Title</th><th scope="col">Category</th><th scope="col">Published</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                        <tbody class="divide-y divide-navy/10 bg-white">
                            @foreach ($articles as $a)
                                <tr>
                                    <td class="font-semibold text-navy">{{ $a->title }}</td>
                                    <td>{{ $a->category?->name ?? '—' }}</td>
                                    <td>{{ $a->published_at?->format('j M Y') ?? '—' }}</td>
                                    <td>@if ($a->trashed())<x-admin.publish-badge :published="false" :archived="true" />@else<x-status-badge :status="$a->status" size="sm" />@endif</td>
                                    <td class="whitespace-nowrap text-right">
                                        @if ($a->trashed())
                                            <x-admin.delete-button :action="route('admin.articles.restore', $a)" method="PATCH" label="Restore" confirm="Restore as draft?" class="btn-secondary btn-sm" />
                                        @else
                                            <a href="{{ route('admin.articles.show', $a) }}" class="btn-text text-sm">Preview</a>
                                            <a href="{{ route('admin.articles.edit', $a) }}" class="btn-secondary btn-sm">Edit</a>
                                            <x-admin.delete-button :action="route('admin.articles.destroy', $a)" label="Delete" confirm="Move this article to the bin?" />
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $articles->links() }}
            @endif
        </div>
        <aside class="card h-fit p-6" aria-labelledby="cat-h">
            <h2 id="cat-h" class="font-semibold">Article categories</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($categories as $c)
                    <li class="flex items-center justify-between gap-2">
                        <span>{{ $c->name }} <span class="text-xs">({{ $c->articles_count }})</span></span>
                        <x-admin.delete-button :action="route('admin.article-categories.destroy', $c)" label="Remove" confirm="Delete this category? Articles will keep existing without a category." class="text-xs font-semibold text-red-700 underline" />
                    </li>
                @empty
                    <li>No categories yet.</li>
                @endforelse
            </ul>
            <form method="POST" action="{{ route('admin.article-categories.store') }}" class="mt-4 space-y-3">
                @csrf
                <x-form.input name="name" label="New category" required />
                <button class="btn-navy btn-sm" type="submit">Add category</button>
            </form>
        </aside>
    </div>
</x-layouts.admin>
