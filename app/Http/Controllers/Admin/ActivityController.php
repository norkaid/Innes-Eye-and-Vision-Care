<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;

class ActivityController extends Controller
{
    public function __invoke()
    {
        return view('admin.activity', ['logs' => AuditLog::with('user')->latest('id')->paginate(40)]);
    }
}
