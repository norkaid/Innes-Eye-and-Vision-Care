<x-layouts.auth title="Reset your password">
    <p class="text-sm">Enter your email address and, if an account exists, we will send you a link to choose a new password.</p>
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5" novalidate>
        @csrf
        <x-form.input name="email" type="email" label="Email address" required autocomplete="email" />
        <button class="btn-primary w-full py-3" type="submit">Email reset link</button>
    </form>
    <p class="text-sm"><a href="{{ route('login') }}">Back to sign in</a></p>
</x-layouts.auth>
