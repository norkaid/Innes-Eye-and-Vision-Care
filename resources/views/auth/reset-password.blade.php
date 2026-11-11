<x-layouts.auth title="Choose a new password">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.input name="email" type="email" label="Email address" required autocomplete="email" :value="$email" />
        <x-form.input name="password" type="password" label="New password" required autocomplete="new-password" hint="At least 10 characters, with upper- and lower-case letters and a number." />
        <x-form.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
        <button class="btn-primary w-full py-3" type="submit">Reset password</button>
    </form>
</x-layouts.auth>
