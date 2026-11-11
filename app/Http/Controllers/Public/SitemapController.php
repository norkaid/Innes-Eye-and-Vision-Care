<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\HealthArticle;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $urls = collect([
                route('home'), route('about'), route('services.index'), route('products.index'),
                route('branches.index'), route('articles.index'), route('contact'), route('appointments.create'),
            ])->map(fn ($u) => ['loc' => $u, 'lastmod' => null]);

            $urls = $urls
                ->merge(Service::published()->get(['slug', 'updated_at'])->map(fn ($s) => ['loc' => route('services.show', $s->slug), 'lastmod' => $s->updated_at]))
                ->merge(Product::published()->get(['slug', 'updated_at'])->map(fn ($p) => ['loc' => route('products.show', $p->slug), 'lastmod' => $p->updated_at]))
                ->merge(Branch::active()->get(['slug', 'updated_at'])->map(fn ($b) => ['loc' => route('branches.show', $b->slug), 'lastmod' => $b->updated_at]))
                ->merge(HealthArticle::published()->get(['slug', 'updated_at'])->map(fn ($a) => ['loc' => route('articles.show', $a->slug), 'lastmod' => $a->updated_at]));

            return view('public.sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots()
    {
        $lines = ['User-agent: *'];
        foreach (['/admin', '/account', '/login', '/register', '/forgot-password', '/reset-password', '/appointments/received'] as $path) {
            $lines[] = 'Disallow: '.$path;
        }
        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain']);
    }
}
