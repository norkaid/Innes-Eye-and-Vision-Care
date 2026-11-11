<x-layouts.admin title="Staff & roles">
    <x-slot:actions><a href="{{ route('admin.staff.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> New staff account</a></x-slot:actions>
    <div class="card overflow-x-auto">
        <table class="table-admin">
            <thead><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Roles</th><th scope="col">Last sign-in</th><th scope="col">Account</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody class="divide-y divide-navy/10 bg-white">
                @foreach ($staff as $m)
                    <tr>
                        <td class="font-semibold text-navy">{{ $m->name }}@if ($m->is(auth()->user())) <span class="text-xs font-normal">(you)</span>@endif</td>
                        <td>{{ $m->email }}</td>
                        <td>{{ $m->roles->pluck('name')->join(', ') ?: '—' }}</td>
                        <td>{{ $m->last_login_at?->format('j M Y, H:i') ?? 'Never' }}</td>
                        <td>{{ $m->is_active ? 'Active' : 'Deactivated' }}</td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('admin.staff.edit', $m) }}" class="btn-secondary btn-sm">Edit</a>
                            @unless ($m->is(auth()->user()))
                                <x-admin.delete-button :action="route('admin.staff.toggle', $m)" method="PATCH" :label="$m->is_active ? 'Deactivate' : 'Reactivate'" :confirm="$m->is_active ? 'Deactivate this staff account?' : 'Reactivate this staff account?'" :class="$m->is_active ? 'btn-danger btn-sm' : 'btn-primary btn-sm'" />
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $staff->links() }}
    <section class="mt-8" aria-labelledby="roles-h">
        <h2 id="roles-h" class="text-lg font-semibold">Roles</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            @foreach ($roles as $r)
                <div class="card p-5">
                    <h3 class="font-semibold">{{ $r->name }}</h3>
                    <p class="mt-1 text-sm">{{ $r->description }}</p>
                    <ul class="mt-3 flex flex-wrap gap-1">
                        @foreach (in_array('*', $r->permissions()) ? ['All permissions'] : array_map(fn ($p) => config('innes.permissions')[$p] ?? $p, $r->permissions()) as $p)
                            <li class="rounded bg-navy-50 px-2 py-0.5 text-xs text-navy">{{ $p }}</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.admin>
