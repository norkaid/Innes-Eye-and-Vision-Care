@props(['action'])
<form method="GET" action="{{ $action }}" role="search" class="card mb-6 grid items-end gap-4 p-4 sm:grid-cols-2 lg:grid-cols-6">
    {{ $slot }}
    <div class="flex gap-2">
        <button type="submit" class="btn-primary w-full"><x-icon name="search" class="h-4 w-4" /> Filter</button>
        <a href="{{ $action }}" class="btn-secondary">Reset</a>
    </div>
</form>
