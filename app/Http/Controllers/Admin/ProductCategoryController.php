<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductCategoryController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index()
    {
        return view('admin.categories.index', ['categories' => ProductCategory::withCount('products')->orderBy('display_order')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.categories.form', ['category' => new ProductCategory(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $category = ProductCategory::create($this->validated($request));
        $this->audit->log('category.created', $category, ['name' => $category->name]);

        return redirect()->route('admin.categories.index')->with('success', "Category \"{$category->name}\" created.");
    }

    public function edit(ProductCategory $category)
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(Request $request, ProductCategory $category)
    {
        $category->update($this->validated($request, $category));
        $this->audit->log('category.updated', $category, ['name' => $category->name]);

        return redirect()->route('admin.categories.index')->with('success', "Category \"{$category->name}\" saved.");
    }

    public function destroy(ProductCategory $category)
    {
        if ($category->products()->withTrashed()->exists()) {
            return back()->with('error', 'This category still contains products. Move or archive them, or mark the category inactive instead.');
        }
        $category->delete();
        $this->audit->log('category.deleted', $category, ['name' => $category->name]);

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }

    private function validated(Request $request, ?ProductCategory $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'alpha_dash', Rule::unique('product_categories', 'slug')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['display_order'] = $data['display_order'] ?? 0;

        return $data;
    }
}
