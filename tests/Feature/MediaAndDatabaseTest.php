<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaAndDatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_content_staff_upload_several_images_into_a_folder(): void
    {
        $this->actingAs($this->staff('content-manager'))->post('/admin/media', [
            'folder' => 'products',
            'alt_text' => 'Frame photo',
            'files' => [UploadedFile::fake()->image('a.jpg', 600, 400)->size(100), UploadedFile::fake()->image('b.png', 300, 300)->size(100)],
        ])->assertRedirect('/admin/media?folder=products')->assertSessionHasNoErrors();

        $this->assertSame(2, Media::where('folder', 'products')->count());
        $media = Media::where('original_name', 'a.jpg')->sole();
        $this->assertSame(600, $media->width);
        $this->assertStringStartsWith('media/products/', $media->path);
        $this->assertNotNull($media->content);
        $this->assertSame($media->size_bytes, strlen($media->content->data));
        Storage::disk('public')->assertDirectoryEmpty('/');

        $this->get('/admin/media?folder=products')->assertOk()->assertSee('a.jpg')->assertSee('b.png');
    }

    public function test_images_are_served_from_the_database(): void
    {
        $this->actingAs($this->staff('content-manager'))->post('/admin/media', [
            'folder' => 'logo', 'files' => [UploadedFile::fake()->image('logo.png', 200, 100)],
        ]);
        $media = Media::sole();
        $this->assertSame(url($media->path), $media->url());

        auth()->logout();
        $response = $this->get('/'.$media->path)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertSame($media->content->data, $response->getContent());
        $this->assertStringContainsString('immutable', $response->headers->get('Cache-Control'));

        $this->get('/'.$media->path, ['If-None-Match' => $response->headers->get('ETag')])->assertStatus(304);
        $this->get('/media/logo/doesnotexist.png')->assertNotFound();
        $this->get('/media/../.env')->assertNotFound();
    }

    public function test_media_upload_rejects_non_images_and_unknown_folders(): void
    {
        $user = $this->staff('content-manager');
        $this->actingAs($user)->post('/admin/media', ['folder' => 'products', 'files' => [UploadedFile::fake()->create('x.svg', 5, 'image/svg+xml')]])->assertSessionHasErrors('files.0');
        $this->actingAs($user)->post('/admin/media', ['folder' => '../etc', 'files' => [UploadedFile::fake()->image('a.jpg')]])->assertSessionHasErrors('folder');
        $this->assertDatabaseCount('media', 0);
    }

    public function test_receptionist_cannot_use_media_library_or_database(): void
    {
        $user = $this->staff('receptionist');
        $this->actingAs($user)->get('/admin/media')->assertForbidden();
        $this->actingAs($user)->get('/admin/database')->assertForbidden();
    }

    public function test_product_can_use_a_library_image_and_library_image_in_use_cannot_be_deleted(): void
    {
        $user = $this->staff('administrator');
        $this->actingAs($user)->post('/admin/media', ['folder' => 'products', 'files' => [UploadedFile::fake()->image('frame.jpg')]]);
        $media = Media::sole();
        $cat = ProductCategory::factory()->create();

        $this->actingAs($user)->post('/admin/products', [
            'name' => 'Aviator', 'product_category_id' => $cat->id, 'availability_status' => 'enquire', 'image_media_id' => $media->id,
        ])->assertSessionHasNoErrors();
        $product = Product::sole();
        $this->assertSame($media->path, $product->image_path);
        $this->actingAs($user)->get("/admin/products/{$product->id}/edit")->assertOk()->assertSee('Choose from media library');

        $this->actingAs($user)->delete("/admin/media/{$media->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('media_contents', ['media_id' => $media->id]);

        // Removing the image from the product keeps the library file.
        $this->actingAs($user)->put("/admin/products/{$product->id}", [
            'name' => 'Aviator', 'product_category_id' => $cat->id, 'availability_status' => 'enquire', 'remove_image' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('media_contents', ['media_id' => $media->id]);

        $this->actingAs($user)->delete("/admin/media/{$media->id}")->assertRedirect();
        $this->assertDatabaseCount('media', 0);
        $this->assertDatabaseCount('media_contents', 0);
    }

    public function test_logo_upload_is_used_on_the_public_site(): void
    {
        $this->actingAs($this->staff('administrator'))->put('/admin/settings', [
            'section' => 'logo', 'logo' => UploadedFile::fake()->image('logo.png', 400, 120),
        ])->assertRedirect('/admin/settings')->assertSessionHasNoErrors();

        $media = Media::where('folder', 'logo')->sole();
        $this->get('/')->assertOk()->assertSee(url($media->path), false);

        $this->actingAs($this->staff('administrator'))->put('/admin/settings', ['section' => 'logo', 'remove_logo' => '1']);
        $this->get('/')->assertDontSee(url($media->path), false);
    }

    public function test_content_manager_cannot_change_logo(): void
    {
        $this->actingAs($this->staff('content-manager'))->put('/admin/settings', [
            'section' => 'logo', 'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertForbidden();
    }

    public function test_super_admin_browses_database_read_only_without_secrets(): void
    {
        $customer = User::factory()->create(['email' => 'browse@example.com']);
        $admin = $this->staff('super-admin');

        $this->actingAs($admin)->get('/admin/database')->assertOk()->assertSee('users')->assertSee('appointments')->assertDontSee('password_reset_tokens');
        $this->actingAs($admin)->get('/admin/database/users')->assertOk()
            ->assertSee('browse@example.com')
            ->assertDontSee($customer->password)
            ->assertSee('Hidden for security: password, remember_token');

        $this->actingAs($admin)->post('/admin/media', ['folder' => 'general', 'files' => [UploadedFile::fake()->image('x.jpg')]]);
        $this->actingAs($admin)->get('/admin/database/media_contents')->assertOk()->assertSee('[image file,');

        $this->actingAs($admin)->get('/admin/database/sessions')->assertNotFound();
        $this->actingAs($admin)->get('/admin/database/no_such_table')->assertNotFound();
        $this->assertDatabaseHas('audit_logs', ['action' => 'database.table_viewed']);
    }

    public function test_administrator_cannot_browse_database(): void
    {
        $this->actingAs($this->staff('administrator'))->get('/admin/database')->assertForbidden();
    }
}
