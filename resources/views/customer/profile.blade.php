<x-layouts.account title="Profile & password">
    <form method="POST" action="{{ route('account.profile.update') }}" class="card space-y-5 p-6" novalidate>
        @csrf @method('PATCH')
        <h2 class="text-lg font-semibold">Contact details</h2>
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="first_name" label="First name" required :value="$user->first_name" autocomplete="given-name" />
            <x-form.input name="last_name" label="Last name" required :value="$user->last_name" autocomplete="family-name" />
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="email" type="email" label="Email address" required :value="$user->email" autocomplete="email" />
            <x-form.input name="phone" type="tel" label="Phone number" :value="$user->phone" autocomplete="tel" />
        </div>
        <button class="btn-primary" type="submit">Save details</button>
    </form>
    <form method="POST" action="{{ route('account.password') }}" class="card space-y-5 p-6" novalidate>
        @csrf @method('PUT')
        <h2 class="text-lg font-semibold">Change password</h2>
        <x-form.input name="current_password" type="password" label="Current password" required autocomplete="current-password" />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="password" type="password" label="New password" required autocomplete="new-password" />
            <x-form.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
        </div>
        <button class="btn-navy" type="submit">Change password</button>
    </form>
</x-layouts.account>
