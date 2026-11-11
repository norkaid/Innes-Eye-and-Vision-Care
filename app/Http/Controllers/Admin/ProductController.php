<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductAvailability;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\AuditLogger;
use App\Services\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(private ImageUploader $images, private AuditLogger $audit) {}

    public function index(Request $request)
    {
        $archived = $request->boolean('archived');
        $q = $request->string('q')->limit(100)->toString();
        $category = $request->integer('category') ?: null;
        $products = Product::with('category')
            ->when($archived, fn ($query) => $query->onlyTrashed())
            ->when($category, fn ($query) => $query->where('product_category_id', $category))
            ->search($q ?: null)
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.products.index', [
            'products' => $products, 'archived' => $archived, 'q' => $q, 'category' => $category,
            'categories' => ProductCategory::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create()
    {
        return view('admin.products.form', ['product' => new Product(['availability_status' => ProductAvailability::Enquire]), 'categories' => ProductCategory::orderBy('name')->pluck('name', 'id')]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $product = new Product($data);
        $product->image_path = $request->hasFile('image') ? $this->images->store($request->file('image'), 'products') : null;
        $product->save();
        $this->audit->log('product.created', $product, ['name' => $product->name]);

        return redirect()->route('admin.products.index')->with('success', "Product \"{$product->name}\" created.");
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', ['product' => $product, 'categories' => ProductCategory::orderBy('name')->pluck('name', 'id')]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product);
        $product->fill($data);
        $product->image_path = $this->images->replace($request->file('image'), $product->image_path, 'products', $request->boolean('remove_image'));
        $product->save();
        $this->audit->log('product.updated', $product, ['name' => $product->name, 'published' => $product->is_published]);

        return redirect()->route('admin.products.index')->with('success', "Product \"{$product->name}\" saved.");
    }

    public function destroy(Product $product)
    {
        $product->is_published = false;
        $product->save();
        $product->delete();
        $this->audit->log('product.archived', $product, ['name' => $product->name]);

        return redirect()->route('admin.products.index')->with('success', "Product \"{$product->name}\" archived.");
    }

    public function restore(Product $product)
    {
        $product->restore();
        $this->audit->log('product.restored', $product);

        return redirect()->route('admin.products.index')->with('success', "Product \"{$product->name}\" restored as a draft.");
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:170', 'alpha_dash', Rule::unique('products', 'slug')->ignore($product?->id)],
            'product_category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'brand' => ['nullable', 'string', 'max:120'],
            'short_description' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'availability_status' => ['required', Rule::enum(ProductAvailability::class)],
            'is_published' => ['boolean'],
            'image' => ImageUploader::rules(),
            'remove_image' => ['boolean'],
        ]);
        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }
}
