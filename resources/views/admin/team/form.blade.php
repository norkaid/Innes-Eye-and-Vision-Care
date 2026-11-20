<x-layouts.admin :title="$member->exists ? 'Edit profile' : 'New team profile'">
    <x-slot:breadcrumb><a href="{{ route('admin.team.index') }}">Team profiles</a></x-slot:breadcrumb>
    <form method="POST" action="{{ $member->exists ? route('admin.team.update', $member) : route('admin.team.store') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-3" novalidate>
        @csrf
        @if ($member->exists) @method('PUT') @endif
        <div class="card space-y-5 p-6 xl:col-span-2">
            <x-form.input name="name" label="Full name" required :value="$member->name" />
            <x-form.input name="slug" label="URL slug" :value="$member->slug" hint="Leave blank to generate from the name." />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="job_title" label="Role / job title" :value="$member->job_title" hint="e.g. Optometrist, Optician, Practice manager." />
                <x-form.input name="qualifications" label="Qualifications" :value="$member->qualifications" hint="Only qualifications verified by the centre." />
            </div>
            <x-form.textarea name="bio" label="Short biography" rows="5" maxlength="2000" :value="$member->bio" />
            <x-admin.image-field :current="$member->image_path" label="Portrait photo" folder="team" />
        </div>
        <div class="space-y-6">
            <div class="card space-y-4 p-6">
                <h2 class="font-semibold">Publishing</h2>
                <x-form.checkbox name="is_published" label="Show on the About page" :checked="(bool) $member->is_published" hint="Only with the person's consent." />
                <x-form.input name="display_order" type="number" label="Display order" min="0" :value="$member->display_order ?? 0" />
            </div>
            <div class="flex gap-3"><button class="btn-primary" type="submit">Save profile</button><a href="{{ route('admin.team.index') }}" class="btn-secondary">Cancel</a></div>
        </div>
    </form>
</x-layouts.admin>
