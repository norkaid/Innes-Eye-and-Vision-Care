<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Branch;

class BranchController extends Controller
{
    public function index()
    {
        return view('public.branches.index', [
            'branches' => Branch::active()->ordered()->with('openingHours')->get(),
        ]);
    }

    public function show(string $slug)
    {
        $branch = Branch::active()->where('slug', $slug)
            ->with(['openingHours', 'services' => fn ($q) => $q->published()->ordered()])
            ->firstOrFail();

        return view('public.branches.show', compact('branch'));
    }
}
