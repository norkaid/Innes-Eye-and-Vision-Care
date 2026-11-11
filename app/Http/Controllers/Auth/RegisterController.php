<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'privacy_consent' => ['accepted'],
        ], [
            'privacy_consent.accepted' => 'Please confirm you agree to the privacy notice.',
        ]);

        $user = new User(collect($data)->only(['first_name', 'last_name', 'email', 'phone', 'password'])->all());
        $user->email = strtolower($user->email);
        $user->account_type = User::TYPE_CUSTOMER;
        $user->is_active = true;
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account.index')->with('success', 'Welcome! Your account has been created.');
    }
}
