<x-layouts.admin :title="$category->exists ? 'Edit category' : 'New category'">
    <x-slot:breadcrumb><a href="{{ route('admin.categories.index') }}">Product categories</a></x-slot:breadcrumb>
    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="card max-w-2xl space-y-5 p-6" novalidate>
        @csrf
        @if ($category->exists) @method('PUT') @endif
        <x-form.input name="name" label="Name" required :value="$category->name" />
        <x-form.input name="slug" label="URL slug" :value="$category->slug" hint="Leave blank to generate from the name." />
        <x-form.textarea name="description" label="Description" rows="3" :value="$category->description" />
        <x-form.input name="display_order" type="number" min="0" label="Display order" :value="$category->display_order ?? 0" />
        <x-form.checkbox name="is_active" label="Visible in the public catalogue" :checked="(bool) $category->is_active" />
        <div class="flex gap-3"><button class="btn-primary" type="submit">Save category</button><a href="{{ route('admin.categories.index') }}" class="btn-secondary">Cancel</a></div>
    </form>
</x-layouts.admin>
