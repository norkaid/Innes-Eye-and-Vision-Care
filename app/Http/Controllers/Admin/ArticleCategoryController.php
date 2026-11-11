<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArticleCategoryController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index()
    {
        return redirect()->route('admin.articles.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120', 'unique:article_categories,name']]);
        $category = ArticleCategory::create($data);
        $this->audit->log('article_category.created', $category, ['name' => $category->name]);

        return back()->with('success', 'Category added.');
    }

    public function update(Request $request, ArticleCategory $articleCategory)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120', Rule::unique('article_categories', 'name')->ignore($articleCategory->id)]]);
        $articleCategory->update($data);

        return back()->with('success', 'Category renamed.');
    }

    public function destroy(ArticleCategory $articleCategory)
    {
        // Articles keep existing; their category is cleared by the foreign key (nullOnDelete).
        $articleCategory->delete();
        $this->audit->log('article_category.deleted', $articleCategory, ['name' => $articleCategory->name]);

        return back()->with('success', 'Category deleted.');
    }
}
