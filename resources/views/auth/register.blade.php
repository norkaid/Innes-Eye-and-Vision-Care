<x-layouts.auth title="Create an account">
    <p class="text-sm">An account lets you view the status of appointment requests you make while signed in. It is optional.</p>
    <form method="POST" action="{{ route('register') }}" class="space-y-5" novalidate>
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="first_name" label="First name" required autocomplete="given-name" />
            <x-form.input name="last_name" label="Last name" required autocomplete="family-name" />
        </div>
        <x-form.input name="email" type="email" label="Email address" required autocomplete="email" />
        <x-form.input name="phone" type="tel" label="Phone number" autocomplete="tel" />
        <x-form.input name="password" type="password" label="Password" required autocomplete="new-password" hint="At least 10 characters, with upper- and lower-case letters and a number." />
        <x-form.input name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" />
        <x-form.checkbox name="privacy_consent" label="I have read the privacy notice and agree to my details being stored for my account." />
        <p class="-mt-3 pl-7 text-sm"><a href="{{ route('privacy') }}">Read the privacy notice</a></p>
        <button class="btn-primary w-full py-3" type="submit">Create account</button>
    </form>
    <p class="text-sm">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
</x-layouts.auth>
