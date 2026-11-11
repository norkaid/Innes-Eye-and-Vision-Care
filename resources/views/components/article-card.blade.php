@props(['article'])
<article class="card group relative flex flex-col overflow-hidden transition-shadow hover:shadow-md">
    <img src="{{ \App\Services\ImageUploader::url($article->featured_image_path) ?? asset('images/placeholder-article.svg') }}" alt="" loading="lazy" class="aspect-[5/3] w-full object-cover" width="400" height="240">
    <div class="flex flex-1 flex-col p-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-teal-700">{{ $article->category?->name ?? 'Eye health' }}</p>
        <h3 class="mt-1 text-lg font-semibold"><a href="{{ route('articles.show', $article->slug) }}" class="text-navy no-underline after:absolute after:inset-0 group-hover:underline">{{ $article->title }}</a></h3>
        @if ($article->excerpt)<p class="mt-2 flex-1 text-sm leading-6">{{ $article->excerpt }}</p>@endif
        <p class="mt-4 text-xs text-slate"><time datetime="{{ $article->published_at?->toDateString() }}">{{ $article->published_at?->format('j M Y') }}</time> · {{ $article->readingMinutes() }} min read</p>
    </div>
</article>
