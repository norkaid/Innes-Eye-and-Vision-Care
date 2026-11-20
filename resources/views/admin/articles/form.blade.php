<x-layouts.admin :title="$article->exists ? 'Edit article' : 'New article'">
    <x-slot:breadcrumb><a href="{{ route('admin.articles.index') }}">Health articles</a></x-slot:breadcrumb>
    @if ($article->exists)
        <x-slot:actions><a href="{{ route('admin.articles.show', $article) }}" class="btn-secondary" target="_blank" rel="noopener">Preview</a></x-slot:actions>
    @endif
    <form method="POST" action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-3" novalidate>
        @csrf
        @if ($article->exists) @method('PUT') @endif
        <div class="card space-y-5 p-6 xl:col-span-2">
            <x-form.input name="title" label="Title" required :value="$article->title" />
            <x-form.input name="slug" label="URL slug" :value="$article->slug" hint="Leave blank to generate from the title." />
            <x-form.textarea name="excerpt" label="Summary" rows="2" maxlength="400" :value="$article->excerpt" />
            <x-form.textarea name="body" label="Article body" required rows="18" :value="$article->body" class="font-mono"
                hint="Written in Markdown: ## Heading, **bold**, - bullet list, [link](https://…). Raw HTML is removed for safety." />
            <x-admin.image-field :current="$article->featured_image_path" label="Featured image" folder="articles" />
        </div>
        <div class="space-y-6">
            <div class="card space-y-4 p-6">
                <h2 class="font-semibold">Publishing</h2>
                <x-alert type="warning" title="Approval required">Clinical content must be reviewed and approved by the centre before publishing.</x-alert>
                <x-form.select name="status" label="Status" required :options="\App\Enums\ArticleStatus::options()" :value="$article->status" />
                <x-form.input name="published_at" type="datetime-local" label="Publication date" :value="$article->published_at?->format('Y-m-d\TH:i')" hint="Leave blank to use the time you publish. A future date schedules the article." />
                <x-form.select name="article_category_id" label="Category" :options="$categories" :value="$article->article_category_id" placeholder="No category" />
                <x-form.input name="author_display_name" label="Author / reviewer credit" :value="$article->author_display_name" hint="Shown publicly. Use only with the person's approval." />
            </div>
            <div class="flex gap-3"><button class="btn-primary" type="submit">Save article</button><a href="{{ route('admin.articles.index') }}" class="btn-secondary">Back</a></div>
        </div>
    </form>
</x-layouts.admin>
