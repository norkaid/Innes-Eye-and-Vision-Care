<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use App\Models\HealthArticle;
use App\Services\AuditLogger;
use App\Services\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    public function __construct(private ImageUploader $images, private AuditLogger $audit) {}

    public function index(Request $request)
    {
        $status = $request->string('status')->toString();
        $articles = HealthArticle::with('category')
            ->when($status === 'trashed', fn ($q) => $q->onlyTrashed())
            ->when(in_array($status, array_column(ArticleStatus::cases(), 'value'), true), fn ($q) => $q->where('status', $status))
            ->latest('updated_at')->paginate(25)->withQueryString();

        return view('admin.articles.index', [
            'articles' => $articles, 'status' => $status,
            'categories' => ArticleCategory::withCount('articles')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.articles.form', ['article' => new HealthArticle(['status' => ArticleStatus::Draft]), 'categories' => ArticleCategory::orderBy('name')->pluck('name', 'id')]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $article = new HealthArticle($data);
        $article->author_id = $request->user()->id;
        $this->applyStatus($article, $data);
        $article->featured_image_path = $request->hasFile('image') ? $this->images->store($request->file('image'), 'articles') : null;
        $article->save();
        $this->audit->log('article.created', $article, ['title' => $article->title, 'status' => $article->status->value]);

        return redirect()->route('admin.articles.edit', $article)->with('success', 'Article saved.');
    }

    /** Staff preview – renders drafts with the public template, never publicly reachable. */
    public function show(HealthArticle $article)
    {
        return view('admin.articles.preview', ['article' => $article->load('category')]);
    }

    public function edit(HealthArticle $article)
    {
        return view('admin.articles.form', ['article' => $article, 'categories' => ArticleCategory::orderBy('name')->pluck('name', 'id')]);
    }

    public function update(Request $request, HealthArticle $article)
    {
        $data = $this->validated($request, $article);
        $article->fill($data);
        $this->applyStatus($article, $data);
        $article->featured_image_path = $this->images->replace($request->file('image'), $article->featured_image_path, 'articles', $request->boolean('remove_image'));
        $article->save();
        $this->audit->log('article.updated', $article, ['title' => $article->title, 'status' => $article->status->value]);

        return redirect()->route('admin.articles.edit', $article)->with('success', 'Article saved.');
    }

    public function destroy(HealthArticle $article)
    {
        $article->status = ArticleStatus::Archived;
        $article->save();
        $article->delete();
        $this->audit->log('article.deleted', $article, ['title' => $article->title]);

        return redirect()->route('admin.articles.index')->with('success', 'Article moved to the bin.');
    }

    public function restore(HealthArticle $article)
    {
        $article->restore();
        $article->status = ArticleStatus::Draft;
        $article->save();
        $this->audit->log('article.restored', $article);

        return redirect()->route('admin.articles.edit', $article)->with('success', 'Article restored as a draft.');
    }

    private function applyStatus(HealthArticle $article, array $data): void
    {
        $article->status = ArticleStatus::from($data['status']);
        if ($article->status === ArticleStatus::Published) {
            $article->published_at = $data['published_at'] ?? $article->published_at ?? now();
        }
    }

    private function validated(Request $request, ?HealthArticle $article = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', 'alpha_dash', Rule::unique('health_articles', 'slug')->ignore($article?->id)],
            'article_category_id' => ['nullable', 'integer', 'exists:article_categories,id'],
            'excerpt' => ['nullable', 'string', 'max:400'],
            'body' => ['required', 'string', 'max:100000'],
            'author_display_name' => ['nullable', 'string', 'max:150'],
            'status' => ['required', Rule::enum(ArticleStatus::class)],
            'published_at' => ['nullable', 'date'],
            'image' => ImageUploader::rules(),
            'remove_image' => ['boolean'],
        ]);
    }
}
