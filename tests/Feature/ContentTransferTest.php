<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Enquiry;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\User;
use App\Services\ContentTransfer;
use App\Services\ImageUploader;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContentTransferTest extends TestCase
{
    use RefreshDatabase;

    private function makeContent(): array
    {
        $media = app(ImageUploader::class)->storeMedia(UploadedFile::fake()->image('frame.jpg', 600, 400), 'products');
        $logo = app(ImageUploader::class)->storeMedia(UploadedFile::fake()->image('logo.png', 400, 100), 'logo');
        app(Settings::class)->put(['logo_path' => $logo->path, 'contact_phone' => '+233 20 000 0000', 'slot_capacity' => 3]);

        $service = Service::factory()->create(['name' => 'Eye Test', 'slug' => 'eye-test', 'image_path' => $media->path]);
        $branch = Branch::factory()->create(['name' => 'Osu Branch', 'slug' => 'osu']);
        $branch->services()->attach($service);
        $category = ProductCategory::factory()->create(['name' => 'Frames', 'slug' => 'frames']);
        $product = Product::factory()->create(['product_category_id' => $category->id, 'name' => 'Aviator', 'slug' => 'aviator', 'price' => 450, 'image_path' => $media->path]);
        $demo = Product::factory()->create(['product_category_id' => $category->id, 'name' => 'Sample (Demo)', 'slug' => 'sample-demo']);

        return compact('media', 'logo', 'service', 'branch', 'product', 'demo');
    }

    /** Simulates moving the file to a fresh site: empties the content tables. */
    private function wipeContent(): void
    {
        foreach (['branch_service', 'branch_opening_hours', 'products', 'product_categories', 'services', 'branches', 'health_articles', 'article_categories', 'media_contents', 'media', 'business_settings'] as $table) {
            DB::table($table)->delete();
        }
        app(Settings::class)->flush();
    }

    public function test_content_moves_to_an_empty_site_with_images_and_links(): void
    {
        $c = $this->makeContent();
        $user = User::factory()->create(['email' => 'private@example.com']);
        Enquiry::factory()->create();
        $transfer = app(ContentTransfer::class);

        $file = $transfer->exportFile();
        $this->assertStringNotContainsString('private@example.com', gzdecode($file));
        $this->assertStringNotContainsString('sample-demo', gzdecode($file));

        $this->wipeContent();
        $result = $transfer->import($transfer->readFile($file));

        $this->assertSame(1, $result['Products']['created']);
        $product = Product::where('slug', 'aviator')->sole();
        $this->assertSame('450.00', (string) $product->price);
        $this->assertSame($c['media']->path, $product->image_path);
        $this->assertSame('frames', $product->category->slug);
        $this->assertNull(Product::where('slug', 'sample-demo')->first());

        $branch = Branch::where('slug', 'osu')->sole();
        $this->assertTrue($branch->services->contains('slug', 'eye-test'));
        $this->assertSame(7, $branch->openingHours()->count());

        $media = Media::where('path', $c['media']->path)->sole();
        $this->assertSame(600, $media->width);
        $this->get('/'.$media->path)->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        $this->assertSame($c['logo']->path, app(Settings::class)->get('logo_path'));
        $this->assertSame(3, app(Settings::class)->int('slot_capacity'));
        $this->get('/products/aviator')->assertOk()->assertSee(url($media->path), false);
    }

    public function test_importing_twice_updates_instead_of_duplicating(): void
    {
        $this->makeContent();
        $transfer = app(ContentTransfer::class);
        $file = $transfer->exportFile();

        Product::where('slug', 'aviator')->update(['name' => 'Changed here']);
        $result = $transfer->import($transfer->readFile($file));

        $this->assertSame(0, $result['Products']['created']);
        $this->assertSame(1, $result['Products']['updated']);
        $this->assertSame('Aviator', Product::where('slug', 'aviator')->value('name'));
        $this->assertSame(2, Media::count());
        $this->assertSame(2, DB::table('media_contents')->count());
    }

    public function test_demo_items_can_be_included_on_request(): void
    {
        $this->makeContent();
        $data = app(ContentTransfer::class)->export(includeDemo: true);
        $this->assertContains('sample-demo', array_column($data['products'], 'slug'));
    }

    public function test_super_admin_exports_and_imports_through_the_dashboard(): void
    {
        $this->makeContent();
        $admin = $this->staff('super-admin');

        $this->actingAs($admin)->get('/admin/content-transfer')->assertOk()->assertSee('Download export file');
        $response = $this->actingAs($admin)->post('/admin/content-transfer/export')->assertOk();
        $file = $response->streamedContent();
        $this->assertStringStartsWith("\x1f\x8b", $file);

        $this->wipeContent();
        $path = tempnam(sys_get_temp_dir(), 'exp');
        file_put_contents($path, $file);
        $upload = new UploadedFile($path, 'innes-content.json.gz', 'application/gzip', null, true);

        $this->actingAs($admin)->post('/admin/content-transfer/import', ['file' => $upload])->assertSessionHasErrors('confirm');
        $this->assertSame(0, Product::count());

        $this->actingAs($admin)->post('/admin/content-transfer/import', ['file' => $upload, 'confirm' => '1'])
            ->assertRedirect('/admin/content-transfer')->assertSessionHas('import_result');
        $this->assertSame(1, Product::count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.imported']);
    }

    public function test_only_super_admin_can_transfer_content(): void
    {
        foreach (['administrator', 'receptionist', 'content-manager'] as $role) {
            $this->actingAs($this->staff($role))->get('/admin/content-transfer')->assertForbidden();
            $this->actingAs($this->staff($role))->post('/admin/content-transfer/export')->assertForbidden();
        }
    }

    public function test_invalid_or_tampered_files_import_nothing(): void
    {
        $admin = $this->staff('super-admin');
        $send = function (string $contents) use ($admin) {
            $path = tempnam(sys_get_temp_dir(), 'exp');
            file_put_contents($path, $contents);

            return $this->actingAs($admin)->post('/admin/content-transfer/import', [
                'file' => new UploadedFile($path, 'content.json.gz', 'application/gzip', null, true), 'confirm' => '1',
            ]);
        };

        $send('not an export')->assertSessionHasErrors('file');

        $category = ['name' => 'Frames', 'slug' => 'frames'];
        $base = ['format' => 'innes-content', 'version' => 1, 'product_categories' => [$category]];

        // A non-image disguised as a picture is rejected, and nothing else is saved.
        $send(gzencode(json_encode($base + ['media' => [[
            'folder' => 'products', 'path' => 'media/products/abcdefghijklmnop.jpg', 'mime_type' => 'image/jpeg',
            'data' => base64_encode('<?php echo "x";'),
        ]]])))->assertSessionHasErrors('file');
        $this->assertSame(0, ProductCategory::count());

        // Paths outside the media library are refused.
        $send(gzencode(json_encode($base + ['media' => [[
            'folder' => 'products', 'path' => '../../.env', 'mime_type' => 'image/jpeg', 'data' => '',
        ]]])))->assertSessionHasErrors('file');

        // Image references to arbitrary files are dropped.
        $send(gzencode(json_encode($base + ['products' => [[
            'name' => 'X', 'slug' => 'x', 'category' => 'frames', 'image_path' => '../.env', 'availability_status' => 'bogus',
        ]]])))->assertSessionHasNoErrors();
        $product = Product::where('slug', 'x')->sole();
        $this->assertNull($product->image_path);
        $this->assertSame('enquire', $product->availability_status->value);
    }

    public function test_commands_export_and_import(): void
    {
        $this->makeContent();
        $path = sys_get_temp_dir().'/innes-cmd-test.json.gz';
        @unlink($path);

        $this->artisan('innes:export-content', ['file' => $path])->assertSuccessful();
        $this->assertFileExists($path);

        $this->wipeContent();
        $this->artisan('innes:import-content', ['file' => $path, '--force' => true])->assertSuccessful();
        $this->assertSame(1, Product::count());
        @unlink($path);
    }
}
