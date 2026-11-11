<x-layouts.admin title="Change my password">
    <form method="POST" action="{{ route('admin.profile.update') }}" class="card max-w-xl space-y-5 p-6" novalidate>
        @csrf @method('PUT')
        <x-form.input name="current_password" type="password" label="Current password" required autocomplete="current-password" />
        <x-form.input name="password" type="password" label="New password" required autocomplete="new-password" hint="At least 10 characters, with upper- and lower-case letters and a number." />
        <x-form.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
        <button class="btn-primary" type="submit">Change password</button>
    </form>
</x-layouts.admin>
