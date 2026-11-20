<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;

class PageController extends Controller
{
    public function about()
    {
        return view('public.about', ['team' => TeamMember::published()->ordered()->get()]);
    }

    public function privacy()
    {
        return view('public.privacy');
    }
}
