<x-layouts.auth title="Sign in">
    <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
        @csrf
        <x-form.input name="email" type="email" label="Email address" required autocomplete="email" autofocus />
        <x-form.input name="password" type="password" label="Password" required autocomplete="current-password" />
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-form.checkbox name="remember" label="Keep me signed in" />
            <a href="{{ route('password.request') }}" class="text-sm">Forgot your password?</a>
        </div>
        <button class="btn-primary w-full py-3" type="submit">Sign in</button>
    </form>
    @if (config('innes.features.customer_accounts'))
        <p class="border-t border-navy/10 pt-5 text-sm">New here? <a href="{{ route('register') }}">Create a customer account</a> to track your appointment requests. An account is not needed to browse or book.</p>
    @endif
</x-layouts.auth>
