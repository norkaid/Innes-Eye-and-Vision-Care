<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Models\Branch;
use App\Models\HealthArticle;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_service_crud_with_branches_and_image(): void
    {
        $admin = $this->staff('content-manager');
        $branch = Branch::factory()->create();

        $this->actingAs($admin)->post('/admin/services', [
            'name' => 'Dry Eye Clinic', 'short_description' => 'Help for dry eyes.', 'is_published' => '1',
            'branches' => [$branch->id], 'image' => UploadedFile::fake()->image('eye.jpg', 800, 600)->size(300),
        ])->assertRedirect('/admin/services')->assertSessionHasNoErrors();

        $service = Service::where('slug', 'dry-eye-clinic')->sole();
        $this->assertTrue($service->is_published);
        $this->assertTrue($service->branches->contains($branch));
        Storage::disk('public')->assertExists($service->image_path);
        $this->get('/services/dry-eye-clinic')->assertOk();

        $this->actingAs($admin)->put("/admin/services/{$service->id}", [
            'name' => 'Dry Eye Clinic', 'short_description' => 'Updated.', 'is_published' => '0', 'branches' => [], 'remove_image' => '1',
        ])->assertSessionHasNoErrors();
        $service->refresh();
        $this->assertFalse($service->is_published);
        $this->assertNull($service->image_path);
        $this->get('/services/dry-eye-clinic')->assertNotFound();

        $this->actingAs($admin)->delete("/admin/services/{$service->id}")->assertRedirect();
        $this->assertSoftDeleted($service);
        $this->actingAs($admin)->patch("/admin/services/{$service->id}/restore")->assertRedirect();
        $this->assertNotSoftDeleted($service);
    }

    public function test_product_crud_without_inventing_price_or_stock(): void
    {
        $admin = $this->staff('content-manager');
        $cat = ProductCategory::factory()->create();

        $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Test Frame', 'product_category_id' => $cat->id, 'availability_status' => 'enquire', 'is_published' => '1',
        ])->assertSessionHasNoErrors();
        $product = Product::where('name', 'Test Frame')->sole();
        $this->assertNull($product->price);
        $this->get('/products/'.$product->slug)->assertOk()->assertDontSee('GH₵');

        $this->actingAs($admin)->put("/admin/products/{$product->id}", [
            'name' => 'Test Frame', 'product_category_id' => $cat->id, 'availability_status' => 'available', 'price' => '450.00', 'is_published' => '1',
        ])->assertSessionHasNoErrors();
        $this->get('/products/'.$product->slug)->assertSee('GH₵ 450.00');

        $this->actingAs($admin)->post('/admin/products', ['name' => '', 'product_category_id' => 999, 'availability_status' => 'in-stock-forever'])
            ->assertSessionHasErrors(['name', 'product_category_id', 'availability_status']);

        $this->actingAs($admin)->delete("/admin/products/{$product->id}");
        $this->assertSoftDeleted($product);
        $this->get('/products/'.$product->slug)->assertNotFound();
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $admin = $this->staff('content-manager');
        $product = Product::factory()->create();
        $empty = ProductCategory::factory()->create();

        $this->actingAs($admin)->delete("/admin/categories/{$product->product_category_id}")->assertSessionHas('error');
        $this->assertDatabaseHas('product_categories', ['id' => $product->product_category_id]);
        $this->actingAs($admin)->delete("/admin/categories/{$empty->id}")->assertSessionHas('success');
        $this->assertDatabaseMissing('product_categories', ['id' => $empty->id]);
    }

    public function test_uploads_reject_invalid_formats_and_oversized_files(): void
    {
        $admin = $this->staff('content-manager');
        $cat = ProductCategory::factory()->create();
        $base = ['name' => 'Upload Test', 'product_category_id' => $cat->id, 'availability_status' => 'enquire'];

        $this->actingAs($admin)->post('/admin/products', $base + ['image' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')])->assertSessionHasErrors('image');
        $this->actingAs($admin)->post('/admin/products', $base + ['image' => UploadedFile::fake()->create('shell.php', 10, 'image/jpeg')])->assertSessionHasErrors('image');
        $this->actingAs($admin)->post('/admin/products', $base + ['image' => UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml')])->assertSessionHasErrors('image');
        $this->actingAs($admin)->post('/admin/products', $base + ['image' => UploadedFile::fake()->image('huge.jpg')->size(5000)])->assertSessionHasErrors('image');
        $this->assertDatabaseCount('products', 0);

        $this->actingAs($admin)->post('/admin/products', $base + ['image' => UploadedFile::fake()->image('ok.png', 400, 300)->size(200)])->assertSessionHasNoErrors();
        $path = Product::sole()->image_path;
        $this->assertStringStartsWith('products/', $path);
        $this->assertStringNotContainsString('ok.png', $path); // random file name
    }

    public function test_branch_crud_with_opening_hours_and_services(): void
    {
        $admin = $this->staff('administrator');
        $service = Service::factory()->create();
        $hours = [];
        foreach (range(1, 7) as $d) {
            $hours[$d] = $d === 7 ? ['is_closed' => '1'] : ['opens_at' => '09:00', 'closes_at' => '17:00'];
        }

        $this->actingAs($admin)->post('/admin/branches', [
            'name' => 'Tema Branch', 'city' => 'Tema', 'phone' => '+233 30 222 2222', 'is_active' => '1',
            'services' => [$service->id], 'hours' => $hours,
        ])->assertSessionHasNoErrors();

        $branch = Branch::where('slug', 'tema-branch')->sole();
        $this->assertCount(7, $branch->openingHours);
        $this->assertTrue($branch->hoursFor(7)->is_closed);
        $this->assertSame('09:00', substr($branch->hoursFor(1)->opens_at, 0, 5));
        $this->get('/branches/tema-branch')->assertOk()->assertSee($service->name)->assertSee('09:00 – 17:00');

        $hours[1] = ['opens_at' => '17:00', 'closes_at' => '09:00'];
        $this->actingAs($admin)->put("/admin/branches/{$branch->id}", ['name' => 'Tema Branch', 'is_active' => '1', 'hours' => $hours])->assertSessionHasErrors('hours.1.closes_at');

        $this->actingAs($admin)->delete("/admin/branches/{$branch->id}");
        $this->assertSoftDeleted($branch);
        $this->get('/branches/tema-branch')->assertNotFound();
    }

    public function test_article_draft_preview_and_publish(): void
    {
        $admin = $this->staff('content-manager');

        $this->actingAs($admin)->post('/admin/articles', ['title' => 'Caring for contact lenses', 'body' => 'Wash your hands **first**.', 'status' => 'draft'])->assertSessionHasNoErrors();
        $article = HealthArticle::sole();
        $this->assertSame(ArticleStatus::Draft, $article->status);
        $this->assertNull($article->published_at);

        // Draft: hidden publicly, visible to staff via preview.
        $this->get('/health-resources/'.$article->slug)->assertNotFound();
        $this->actingAs($admin)->get("/admin/articles/{$article->id}")->assertOk()->assertSee('<strong>first</strong>', false);
        $this->actingAs(User::factory()->create())->get("/admin/articles/{$article->id}")->assertForbidden();

        $this->actingAs($admin)->put("/admin/articles/{$article->id}", ['title' => 'Caring for contact lenses', 'body' => 'Wash your hands **first**.', 'status' => 'published'])->assertSessionHasNoErrors();
        $this->assertNotNull($article->fresh()->published_at);
        $this->get('/health-resources/'.$article->slug)->assertOk();

        $this->actingAs($admin)->put("/admin/articles/{$article->id}", ['title' => 'Caring for contact lenses', 'body' => 'x', 'status' => 'archived']);
        $this->get('/health-resources/'.$article->slug)->assertNotFound();
    }

    public function test_content_changes_are_audited(): void
    {
        $admin = $this->staff('content-manager');
        $this->actingAs($admin)->post('/admin/services', ['name' => 'Audit Me', 'short_description' => 'x']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'service.created', 'user_id' => $admin->id]);
    }
}
