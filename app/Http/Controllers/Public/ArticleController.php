<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use App\Models\HealthArticle;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $categories = ArticleCategory::whereHas('articles', fn ($q) => $q->published())->orderBy('name')->get();
        $category = $categories->firstWhere('slug', $request->string('category')->toString());

        $articles = HealthArticle::published()->with('category')
            ->when($category, fn ($q) => $q->where('article_category_id', $category->id))
            ->latest('published_at')
            ->paginate(9)->withQueryString();

        return view('public.articles.index', compact('articles', 'categories', 'category'));
    }

    public function show(string $slug)
    {
        $article = HealthArticle::published()->with(['category', 'author'])->where('slug', $slug)->firstOrFail();
        $more = HealthArticle::published()->with('category')->whereKeyNot($article->id)->latest('published_at')->limit(3)->get();

        return view('public.articles.show', compact('article', 'more'));
    }
}
