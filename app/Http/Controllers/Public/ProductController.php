<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categories = ProductCategory::active()->orderBy('display_order')->orderBy('name')
            ->withCount(['products' => fn ($q) => $q->where('is_published', true)])->get();
        $category = $categories->firstWhere('slug', $request->string('category')->toString());
        $q = trim($request->string('q')->toString());

        $products = Product::published()->with('category')
            ->when($category, fn ($query) => $query->where('product_category_id', $category->id))
            ->search($q ?: null)
            ->orderBy('name')
            ->paginate(12)->withQueryString();

        return view('public.products.index', compact('products', 'categories', 'category', 'q'));
    }

    public function show(string $slug)
    {
        $product = Product::published()->with('category')->where('slug', $slug)->firstOrFail();
        $related = Product::published()->with('category')
            ->where('product_category_id', $product->product_category_id)
            ->whereKeyNot($product->id)->limit(4)->get();

        return view('public.products.show', compact('product', 'related'));
    }
}
