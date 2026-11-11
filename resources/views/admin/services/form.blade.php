<x-layouts.admin :title="$service->exists ? 'Edit service' : 'New service'">
    <x-slot:breadcrumb><a href="{{ route('admin.services.index') }}">Services</a></x-slot:breadcrumb>
    <form method="POST" action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-3" novalidate>
        @csrf
        @if ($service->exists) @method('PUT') @endif
        <div class="card space-y-5 p-6 xl:col-span-2">
            <x-form.input name="name" label="Service name" required :value="$service->name" />
            <x-form.input name="slug" label="URL slug" :value="$service->slug" hint="Leave blank to generate from the name. Letters, numbers and dashes only." />
            <x-form.textarea name="short_description" label="Short description" required rows="2" maxlength="300" :value="$service->short_description" hint="Shown on service cards. Must be approved by the centre." />
            <x-form.textarea name="description" label="What the service involves" rows="8" :value="$service->description" />
            <x-form.textarea name="preparation" label="Preparation information (supplied by the clinic)" rows="4" :value="$service->preparation" />
            <x-admin.image-field :current="$service->image_path" />
        </div>
        <div class="space-y-6">
            <div class="card space-y-4 p-6">
                <h2 class="font-semibold">Publishing</h2>
                <x-form.checkbox name="is_published" label="Published on the website" :checked="(bool) $service->is_published" hint="Only publish services confirmed by the centre." />
                <x-form.input name="display_order" type="number" label="Display order" min="0" :value="$service->display_order ?? 0" />
            </div>
            <fieldset class="card space-y-3 p-6">
                <legend class="sr-only">Branches offering this service</legend>
                <h2 class="font-semibold" aria-hidden="true">Offered at</h2>
                @forelse ($branches as $b)
                    <x-form.checkbox name="branches[]" :value="$b->id" :label="$b->name.($b->is_active ? '' : ' (inactive)')" :checked="in_array($b->id, $selected)" />
                @empty
                    <p class="text-sm">Create a branch first.</p>
                @endforelse
                @error('branches')<p class="form-error">{{ $message }}</p>@enderror
            </fieldset>
            <div class="flex gap-3"><button class="btn-primary" type="submit">Save service</button><a href="{{ route('admin.services.index') }}" class="btn-secondary">Cancel</a></div>
        </div>
    </form>
</x-layouts.admin>
