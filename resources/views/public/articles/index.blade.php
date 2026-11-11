<x-layouts.public title="Eye-health resources" description="Eye-care tips and information from {{ $site->get('business_name') }}.">
    <x-page-header title="Eye-health resources" eyebrow="Learn" intro="Practical information to help you look after your eyes. This content is general guidance and is not a substitute for a professional examination or diagnosis." />
    <section class="container-page py-12">
        @if ($categories->isNotEmpty())
            <nav aria-label="Article categories" class="mb-8">
                <ul class="flex flex-wrap gap-2">
                    <li><a href="{{ route('articles.index') }}" @if (! $category) aria-current="true" @endif class="rounded-full px-4 py-1.5 text-sm font-semibold no-underline ring-1 ring-navy/20 {{ ! $category ? 'bg-navy text-white' : 'text-navy hover:bg-sky' }}">All</a></li>
                    @foreach ($categories as $c)
                        <li><a href="{{ route('articles.index', ['category' => $c->slug]) }}" @if ($category?->id === $c->id) aria-current="true" @endif class="rounded-full px-4 py-1.5 text-sm font-semibold no-underline ring-1 ring-navy/20 {{ $category?->id === $c->id ? 'bg-navy text-white' : 'text-navy hover:bg-sky' }}">{{ $c->name }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif
        @if ($articles->isNotEmpty())
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($articles as $article)<x-article-card :article="$article" />@endforeach
            </div>
            {{ $articles->links() }}
        @else
            <x-empty-state title="No articles yet" icon="book">Articles will be published once reviewed and approved by the centre.</x-empty-state>
        @endif
    </section>
</x-layouts.public>
