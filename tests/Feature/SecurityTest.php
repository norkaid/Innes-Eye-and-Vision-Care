<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Service;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_csrf_protection_is_active_for_public_forms(): void
    {
        // The framework skips CSRF checks during tests; re-enable them for this test.
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });

        $this->post('/contact', ['name' => 'x'])->assertStatus(419);
        $this->post('/appointments', [])->assertStatus(419);
        $this->post('/login', [])->assertStatus(419);
    }

    public function test_forms_include_csrf_tokens(): void
    {
        foreach (['/contact', '/appointments/book', '/login', '/register'] as $url) {
            $this->get($url)->assertSee('name="_token"', false);
        }
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('Referrer-Policy');
    }

    public function test_public_responses_do_not_expose_internal_data(): void
    {
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();
        $a = Appointment::factory()->create(['branch_id' => $branch->id, 'service_id' => $service->id, 'customer_email' => 'secret-person@example.com']);
        $a->staff_note = 'TOP-SECRET';
        $a->save();

        $json = $this->getJson("/appointments/availability?branch={$branch->id}&date=2026-03-04")->assertOk();
        $this->assertSame(['slots', 'message'], array_keys($json->json()));

        foreach (['/', '/branches/'.$branch->slug, '/services/'.$service->slug, '/appointments/book', '/sitemap.xml'] as $url) {
            $this->get($url)->assertDontSee('secret-person@example.com')->assertDontSee('TOP-SECRET')->assertDontSee($a->reference_code);
        }
    }

    public function test_output_is_escaped(): void
    {
        $service = Service::factory()->create(['name' => '<script>alert(1)</script>Exam', 'short_description' => '<img src=x onerror=alert(1)>']);
        $this->get('/services')->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<img src=x onerror', false)->assertSee('&lt;script&gt;', false);
    }

    public function test_no_secrets_are_committed(): void
    {
        $root = base_path();
        $this->assertFileExists($root.'/.env.example');
        $gitignore = file_get_contents($root.'/.gitignore');
        $this->assertStringContainsString('.env', $gitignore);

        $example = file_get_contents($root.'/.env.example');
        $this->assertMatchesRegularExpression('/^APP_KEY=$/m', $example);
        $this->assertMatchesRegularExpression('/^DB_PASSWORD=$/m', $example);
        $this->assertMatchesRegularExpression('/^INITIAL_ADMIN_PASSWORD=$/m', $example);

        // Tracked files must not contain private keys or real-looking API tokens.
        $files = array_filter(explode("\n", (string) shell_exec('git -C '.escapeshellarg($root).' ls-files 2>/dev/null')));
        foreach ($files as $file) {
            if (str_starts_with($file, 'tests/') || ! is_file($root.'/'.$file) || filesize($root.'/'.$file) > 500000) {
                continue;
            }
            $content = file_get_contents($root.'/'.$file);
            $this->assertDoesNotMatchRegularExpression('/-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY-----/', $content, "Private key found in $file");
            $this->assertDoesNotMatchRegularExpression('/base64:[A-Za-z0-9+\/]{40,}={0,2}/', $content, "Application key found in $file");
        }
        $this->assertNotContains('.env', $files);
    }

    public function test_demo_seeder_refuses_to_run_in_production(): void
    {
        $this->app['env'] = 'production';
        $this->expectException(\RuntimeException::class);
        (new DemoContentSeeder)->run();
    }
}
