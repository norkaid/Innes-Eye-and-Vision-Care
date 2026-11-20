<x-layouts.admin title="Team profiles">
    <x-slot:actions><a href="{{ route('admin.team.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> New profile</a></x-slot:actions>
    <x-alert type="info" class="mb-6">Profiles appear on the About page when published. Publish a profile only with the person's consent, and list only qualifications the centre has verified.</x-alert>
    @if ($members->isEmpty())
        <x-empty-state title="No team profiles yet" icon="users">Add practitioners and staff to show them on the About page.</x-empty-state>
    @else
        <div class="card overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th scope="col">Order</th><th scope="col">Name</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody class="divide-y divide-navy/10 bg-white">
                    @foreach ($members as $m)
                        <tr>
                            <td>{{ $m->display_order }}</td>
                            <td class="flex items-center gap-3">
                                @if ($m->image_path)<img src="{{ \App\Services\ImageUploader::url($m->image_path) }}" alt="" class="h-10 w-10 rounded-full object-cover">@endif
                                <span class="font-semibold text-navy">{{ $m->name }}</span>
                            </td>
                            <td>{{ $m->job_title }}</td>
                            <td><x-admin.publish-badge :published="$m->is_published" /></td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.team.edit', $m) }}" class="btn-secondary btn-sm">Edit</a>
                                <x-admin.delete-button :action="route('admin.team.destroy', $m)" confirm="Delete this profile permanently?" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.admin>
