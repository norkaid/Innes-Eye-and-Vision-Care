<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/** Lets any staff member change their own password. */
class ProfileController extends Controller
{
    public function edit()
    {
        return view('admin.profile');
    }

    public function update(Request $request, AuditLogger $audit)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        $request->user()->forceFill(['password' => $request->input('password')])->save();
        $audit->log('staff.password_changed', $request->user());

        return back()->with('success', 'Your password has been changed.');
    }
}
