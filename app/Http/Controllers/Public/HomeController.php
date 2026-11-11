<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\HealthArticle;
use App\Models\Product;
use App\Models\Service;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('public.home', [
            'services' => Service::published()->ordered()->limit(6)->get(),
            'products' => Product::published()->with('category')->latest()->limit(4)->get(),
            'branches' => Branch::active()->ordered()->limit(3)->get(),
            'articles' => HealthArticle::published()->with('category')->latest('published_at')->limit(3)->get(),
        ]);
    }
}
