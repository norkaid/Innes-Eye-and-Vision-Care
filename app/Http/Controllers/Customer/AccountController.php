<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('customer.index', [
            'user' => $user,
            'upcoming' => $user->appointments()->with(['branch', 'service'])
                ->whereIn('status', ['pending', 'confirmed', 'reschedule_requested', 'reschedule_proposed'])
                ->orderBy('requested_date')->limit(5)->get(),
        ]);
    }

    public function edit(Request $request)
    {
        return view('customer.profile', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
        ]);
        $user->fill($data)->save();

        return back()->with('success', 'Your details have been updated.');
    }

    public function password(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        $request->user()->forceFill(['password' => $request->input('password')])->save();

        return back()->with('success', 'Your password has been changed.');
    }

    public function enquiries(Request $request)
    {
        // Only the customer's own enquiries; staff-only notes are never loaded.
        $enquiries = $request->user()->enquiries()->latest()->paginate(10);

        return view('customer.enquiries', compact('enquiries'));
    }
}
