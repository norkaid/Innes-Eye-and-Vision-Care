<x-layouts.admin :title="$member->exists ? 'Edit staff account' : 'New staff account'">
    <x-slot:breadcrumb><a href="{{ route('admin.staff.index') }}">Staff &amp; roles</a></x-slot:breadcrumb>
    @php $isSelf = $member->exists && $member->is(auth()->user()); @endphp
    <form method="POST" action="{{ $member->exists ? route('admin.staff.update', $member) : route('admin.staff.store') }}" class="grid gap-6 xl:grid-cols-3" novalidate>
        @csrf
        @if ($member->exists) @method('PUT') @endif
        <div class="card space-y-5 p-6 xl:col-span-2">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="first_name" label="First name" required :value="$member->first_name" />
                <x-form.input name="last_name" label="Last name" required :value="$member->last_name" />
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="email" type="email" label="Email (sign-in)" required :value="$member->email" />
                <x-form.input name="phone" type="tel" label="Phone" :value="$member->phone" />
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="password" type="password" :label="$member->exists ? 'New password' : 'Initial password'" :required="! $member->exists" autocomplete="new-password" :hint="$member->exists ? 'Leave blank to keep the current password.' : 'Share securely; ask them to change it after signing in.'" />
                <x-form.input name="password_confirmation" type="password" label="Confirm password" :required="! $member->exists" autocomplete="new-password" />
            </div>
        </div>
        <div class="space-y-6">
            <fieldset class="card space-y-3 p-6">
                <legend class="sr-only">Roles</legend>
                <h2 class="font-semibold" aria-hidden="true">Roles</h2>
                @if ($isSelf)
                    <p class="text-sm">You cannot change your own roles. Current: <strong>{{ $member->roles->pluck('name')->join(', ') }}</strong></p>
                @else
                    <p class="text-sm">You can only assign roles whose permissions you hold yourself.</p>
                    @foreach ($roles as $r)
                        <x-form.checkbox name="roles[]" :value="$r->id" :label="$r->name" :hint="$r->description" :checked="in_array($r->id, $selected)" />
                    @endforeach
                    @error('roles')<p class="form-error">{{ $message }}</p>@enderror
                    @error('roles.*')<p class="form-error">{{ $message }}</p>@enderror
                @endif
            </fieldset>
            <div class="flex gap-3"><button class="btn-primary" type="submit">Save</button><a href="{{ route('admin.staff.index') }}" class="btn-secondary">Cancel</a></div>
        </div>
    </form>
</x-layouts.admin>
