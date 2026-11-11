<x-layouts.admin :title="'Edit '.$customer->name">
    <x-slot:breadcrumb><a href="{{ route('admin.customers.index') }}">Customers</a> / <a href="{{ route('admin.customers.show', $customer) }}">{{ $customer->name }}</a></x-slot:breadcrumb>
    <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="card max-w-2xl space-y-5 p-6" novalidate>
        @csrf @method('PATCH')
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="first_name" label="First name" required :value="$customer->first_name" />
            <x-form.input name="last_name" label="Last name" required :value="$customer->last_name" />
        </div>
        <x-form.input name="email" type="email" label="Email" required :value="$customer->email" />
        <x-form.input name="phone" type="tel" label="Phone" :value="$customer->phone" />
        <div class="flex gap-3"><button class="btn-primary" type="submit">Save</button><a href="{{ route('admin.customers.show', $customer) }}" class="btn-secondary">Cancel</a></div>
    </form>
</x-layouts.admin>
