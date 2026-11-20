<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Media;
use App\Models\TeamMember;
use App\Services\ContentTransfer;
use App\Services\ImageUploader;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeamAndWebsiteImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_manager_manages_team_profiles_shown_on_about_page(): void
    {
        $user = $this->staff('content-manager');

        $this->actingAs($user)->post('/admin/team', [
            'name' => 'Ama Owusu', 'job_title' => 'Optometrist', 'qualifications' => 'OD', 'bio' => 'Leads eye examinations.',
            'is_published' => '1', 'image' => UploadedFile::fake()->image('ama.jpg', 400, 400),
        ])->assertRedirect('/admin/team')->assertSessionHasNoErrors();

        $member = TeamMember::sole();
        $this->assertStringStartsWith('media/team/', $member->image_path);
        $this->get('/about')->assertOk()->assertSee('Ama Owusu')->assertSee('Optometrist')->assertSee(url($member->image_path), false);

        $this->actingAs($user)->put("/admin/team/{$member->id}", ['name' => 'Ama Owusu', 'is_published' => '0'])->assertSessionHasNoErrors();
        $this->get('/about')->assertDontSee('Ama Owusu');

        $this->actingAs($user)->delete("/admin/team/{$member->id}")->assertRedirect();
        $this->assertDatabaseCount('team_members', 0);
    }

    public function test_receptionist_cannot_manage_team(): void
    {
        $this->actingAs($this->staff('receptionist'))->get('/admin/team')->assertForbidden();
        $this->actingAs($this->staff('receptionist'))->post('/admin/team', ['name' => 'X'])->assertForbidden();
    }

    public function test_hero_and_about_images_come_from_media_library(): void
    {
        $admin = $this->staff('administrator');
        $hero = app(ImageUploader::class)->storeMedia(UploadedFile::fake()->image('clinic.jpg', 1200, 900), 'general', 'Our reception area');
        $about = app(ImageUploader::class)->storeMedia(UploadedFile::fake()->image('team.jpg', 800, 600), 'general');

        $this->actingAs($admin)->put('/admin/settings', ['section' => 'images', 'hero_image' => $hero->id, 'about_image' => $about->id])
            ->assertRedirect('/admin/settings')->assertSessionHasNoErrors();

        $this->get('/')->assertOk()->assertSee(url($hero->path), false)->assertSee('Our reception area');
        $this->get('/about')->assertOk()->assertSee(url($about->path), false);
        $this->assertContains('Home page main picture', $hero->usages());

        $this->actingAs($admin)->put('/admin/settings', ['section' => 'images', 'hero_image' => 'none']);
        $this->get('/')->assertDontSee(url($hero->path), false)->assertSee('hero-illustration.svg');

        $this->actingAs($admin)->put('/admin/settings', ['section' => 'images', 'hero_image' => 99999])->assertSessionHasErrors('hero_image');
    }

    public function test_branch_photo_upload_shows_on_public_pages(): void
    {
        $branch = Branch::factory()->create(['is_active' => true]);
        $hours = [];
        foreach (range(1, 7) as $d) {
            $hours[$d] = ['opens_at' => '09:00', 'closes_at' => '17:00'];
        }

        $this->actingAs($this->staff('administrator'))->put("/admin/branches/{$branch->id}", [
            'name' => $branch->name, 'is_active' => '1', 'hours' => $hours,
            'image' => UploadedFile::fake()->image('branch.jpg', 1000, 700),
        ])->assertSessionHasNoErrors();

        $path = $branch->fresh()->image_path;
        $this->assertStringStartsWith('media/branches/', $path);
        $this->get('/branches')->assertSee(url($path), false);
        $this->get("/branches/{$branch->slug}")->assertSee(url($path), false);
        $this->assertContains("Branch: {$branch->name}", Media::where('path', $path)->sole()->usages());
    }

    public function test_content_transfer_includes_team_branch_photos_and_website_images(): void
    {
        $photo = app(ImageUploader::class)->storeMedia(UploadedFile::fake()->image('p.jpg'), 'team');
        TeamMember::factory()->create(['name' => 'Kofi Mensah', 'slug' => 'kofi', 'image_path' => $photo->path]);
        Branch::factory()->create(['slug' => 'osu', 'image_path' => $photo->path]);
        app(Settings::class)->put(['hero_image_path' => $photo->path, 'about_image_path' => '../.env']);

        $transfer = app(ContentTransfer::class);
        $file = $transfer->exportFile();
        foreach (['team_members', 'branch_opening_hours', 'branch_service', 'branches', 'media_contents', 'media', 'business_settings'] as $t) {
            DB::table($t)->delete();
        }
        app(Settings::class)->flush();

        $result = $transfer->import($transfer->readFile($file));
        $this->assertSame(1, $result['Team profiles']['created']);
        $this->assertSame($photo->path, TeamMember::where('slug', 'kofi')->value('image_path'));
        $this->assertSame($photo->path, Branch::where('slug', 'osu')->value('image_path'));
        $this->assertSame($photo->path, app(Settings::class)->get('hero_image_path'));
        $this->assertNull(app(Settings::class)->get('about_image_path'));
    }

    public function test_business_name_with_ampersand_is_not_double_escaped(): void
    {
        foreach (['/about', '/services', '/products', '/branches', '/contact', '/health-resources', '/privacy', '/appointments/book'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('&amp;amp;', $html, $url);
        }
        $this->get('/about')->assertSee('About Innes Eye &amp; Visioncare Centre', false);
    }
}
