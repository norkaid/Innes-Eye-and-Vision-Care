<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\HealthArticle;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_load(): void
    {
        foreach (['/', '/about', '/privacy', '/services', '/products', '/branches', '/health-resources', '/contact', '/appointments/book', '/appointments/received', '/login', '/register', '/forgot-password'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml');
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin');
    }

    public function test_pages_have_seo_metadata(): void
    {
        $this->get('/services')
            ->assertSee('<title>Eye-care services |', false)
            ->assertSee('<meta name="description"', false)
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('property="og:title"', false);
    }

    public function test_detail_pages_load_for_published_content(): void
    {
        $service = Service::factory()->create();
        $product = Product::factory()->create();
        $branch = Branch::factory()->create();
        $article = HealthArticle::factory()->create();

        $this->get("/services/{$service->slug}")->assertOk()->assertSee($service->name);
        $this->get("/products/{$product->slug}")->assertOk()->assertSee($product->name)->assertSee('Enquire About This Product');
        $this->get("/branches/{$branch->slug}")->assertOk()->assertSee($branch->name)->assertSee('Opening hours');
        $this->get("/health-resources/{$article->slug}")->assertOk()->assertSee($article->title)->assertSee('not a substitute for a professional');
    }

    public function test_only_published_content_is_visible(): void
    {
        $published = Service::factory()->create(['name' => 'Visible Service']);
        $draft = Service::factory()->draft()->create(['name' => 'Hidden Service']);
        $draftProduct = Product::factory()->draft()->create(['name' => 'Hidden Frame']);
        $hiddenCategoryProduct = Product::factory()->create(['name' => 'Frame In Hidden Category', 'product_category_id' => ProductCategory::factory()->create(['is_active' => false])->id]);
        $draftArticle = HealthArticle::factory()->draft()->create(['title' => 'Hidden Article']);
        $futureArticle = HealthArticle::factory()->create(['title' => 'Scheduled Article']);
        $futureArticle->published_at = now()->addWeek();
        $futureArticle->save();
        $inactiveBranch = Branch::factory()->inactive()->create(['name' => 'Closed Branch']);

        $this->get('/services')->assertSee('Visible Service')->assertDontSee('Hidden Service');
        $this->get("/services/{$draft->slug}")->assertNotFound();
        $this->get('/products')->assertDontSee('Hidden Frame')->assertDontSee('Frame In Hidden Category');
        $this->get("/products/{$draftProduct->slug}")->assertNotFound();
        $this->get("/products/{$hiddenCategoryProduct->slug}")->assertNotFound();
        $this->get('/health-resources')->assertDontSee('Hidden Article')->assertDontSee('Scheduled Article');
        $this->get("/health-resources/{$draftArticle->slug}")->assertNotFound();
        $this->get("/health-resources/{$futureArticle->slug}")->assertNotFound();
        $this->get('/branches')->assertDontSee('Closed Branch');
        $this->get("/branches/{$inactiveBranch->slug}")->assertNotFound();
        $this->get('/sitemap.xml')->assertSee($published->slug)->assertDontSee($draft->slug);
    }

    public function test_product_search_and_category_filters(): void
    {
        $frames = ProductCategory::factory()->create(['name' => 'Frames']);
        $sun = ProductCategory::factory()->create(['name' => 'Sunglasses']);
        Product::factory()->create(['name' => 'Round Titanium', 'brand' => 'Alpha', 'product_category_id' => $frames->id]);
        Product::factory()->create(['name' => 'Square Acetate', 'brand' => 'Beta', 'product_category_id' => $frames->id]);
        Product::factory()->create(['name' => 'Aviator Shades', 'brand' => 'Alpha', 'product_category_id' => $sun->id]);

        $this->get('/products?category='.$sun->slug)->assertSee('Aviator Shades')->assertDontSee('Round Titanium');
        $this->get('/products?q=Titanium')->assertSee('Round Titanium')->assertDontSee('Square Acetate')->assertDontSee('Aviator Shades');
        $this->get('/products?q=Alpha&category='.$frames->slug)->assertSee('Round Titanium')->assertDontSee('Aviator Shades')->assertDontSee('Square Acetate');
        $this->get('/products?q=nothing-matches')->assertSee('No products found');
    }

    public function test_service_search_and_branch_filter(): void
    {
        $branch = Branch::factory()->create();
        $a = Service::factory()->create(['name' => 'Glaucoma Screening']);
        $b = Service::factory()->create(['name' => 'Contact Lens Fitting']);
        $branch->services()->attach($a);

        $this->get('/services?q=glaucoma')->assertSee('Glaucoma Screening')->assertDontSee('Contact Lens Fitting');
        $this->get('/services?branch='.$branch->slug)->assertSee('Glaucoma Screening')->assertDontSee('Contact Lens Fitting');
    }

    public function test_products_are_paginated(): void
    {
        $cat = ProductCategory::factory()->create();
        Product::factory()->count(15)->create(['product_category_id' => $cat->id]);

        $this->get('/products')->assertSee('Showing 1–12 of 15')->assertSee('aria-label="Pagination"', false);
        $this->get('/products?page=2')->assertOk()->assertSee('Showing 13–15 of 15');
    }

    public function test_article_markdown_is_sanitised(): void
    {
        $article = HealthArticle::factory()->create(['body' => "## Heading\n\n<script>alert('x')</script>\n\n[bad](javascript:alert(1))"]);

        $this->get("/health-resources/{$article->slug}")
            ->assertOk()
            ->assertSee('<h2>Heading</h2>', false)
            ->assertDontSee("<script>alert('x')</script>", false)
            ->assertDontSee('javascript:alert', false);
    }
}
