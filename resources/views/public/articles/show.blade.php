<x-layouts.public :title="$article->title" :description="$article->excerpt" :image="\App\Services\ImageUploader::url($article->featured_image_path)">
    <article>
        <x-page-header :title="$article->title" :eyebrow="$article->category?->name ?? 'Eye health'">
            <x-slot:breadcrumbs><x-breadcrumbs :items="['Eye health' => route('articles.index'), $article->title => null]" /></x-slot:breadcrumbs>
            <p class="mt-4 text-sm">
                <time datetime="{{ $article->published_at->toDateString() }}">Published {{ $article->published_at->format('j F Y') }}</time>
                @if ($article->author_display_name) · By {{ $article->author_display_name }}@endif
                · {{ $article->readingMinutes() }} min read
            </p>
        </x-page-header>
        <div class="container-page max-w-3xl py-12">
            @if ($article->featured_image_path)
                <img src="{{ \App\Services\ImageUploader::url($article->featured_image_path) }}" alt="" class="mb-8 w-full rounded-2xl">
            @endif
            @if ($article->excerpt)<p class="text-xl leading-8 text-navy">{{ $article->excerpt }}</p>@endif
            <div class="prose-content mt-6">{{ $article->renderedBody() }}</div>
            <x-alert type="info" title="This is not medical advice" class="mt-10">
                Educational content is not a substitute for a professional eye examination or diagnosis. If you notice sudden changes in your vision, eye pain or injury, seek professional care promptly.
            </x-alert>
            <div class="mt-8"><a href="{{ route('appointments.create') }}" class="btn-primary">Book Appointment</a></div>
        </div>
    </article>
    @if ($more->isNotEmpty())
        <section class="bg-navy-50 py-14" aria-labelledby="more-heading">
            <div class="container-page">
                <h2 id="more-heading" class="font-display text-2xl font-bold">More eye-health articles</h2>
                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    @foreach ($more as $a)<x-article-card :article="$a" />@endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.public>
