<x-layouts.admin :title="'Preview: '.$article->title">
    <x-slot:breadcrumb><a href="{{ route('admin.articles.index') }}">Health articles</a></x-slot:breadcrumb>
    <x-slot:actions><a href="{{ route('admin.articles.edit', $article) }}" class="btn-secondary">Edit</a></x-slot:actions>
    <x-alert type="info" title="Staff preview">Status: {{ $article->status->label() }}. Drafts are only visible to authorised staff.</x-alert>
    <article class="card mt-6 max-w-3xl p-8">
        <p class="text-sm font-semibold uppercase tracking-wider text-teal-700">{{ $article->category?->name ?? 'Eye health' }}</p>
        <h2 class="mt-1 font-display text-3xl font-bold">{{ $article->title }}</h2>
        @if ($article->excerpt)<p class="mt-4 text-lg text-navy">{{ $article->excerpt }}</p>@endif
        <div class="prose-content mt-6">{{ $article->renderedBody() }}</div>
    </article>
</x-layouts.admin>
