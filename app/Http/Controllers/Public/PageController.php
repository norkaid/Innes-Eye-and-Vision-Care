<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function about()
    {
        return view('public.about');
    }

    public function privacy()
    {
        return view('public.privacy');
    }
}
