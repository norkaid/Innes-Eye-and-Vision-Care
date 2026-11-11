<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::active()->ordered()->get(['id', 'name', 'slug']);
        $branch = $branches->firstWhere('slug', $request->string('branch')->toString());
        $q = trim($request->string('q')->toString());

        $services = Service::published()->ordered()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('short_description', 'like', "%{$q}%")))
            ->when($branch, fn ($query) => $query->whereHas('branches', fn ($b) => $b->whereKey($branch->id)))
            ->paginate(12)->withQueryString();

        return view('public.services.index', compact('services', 'branches', 'branch', 'q'));
    }

    public function show(string $slug)
    {
        $service = Service::published()->where('slug', $slug)
            ->with(['branches' => fn ($q) => $q->active()->ordered()])
            ->firstOrFail();
        $related = Service::published()->ordered()->whereKeyNot($service->id)->limit(3)->get();

        return view('public.services.show', compact('service', 'related'));
    }
}
