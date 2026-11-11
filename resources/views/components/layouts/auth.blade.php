@props(['title'])
<x-layouts.public :title="$title" :noindex="true">
    <section class="bg-sky/60 py-14">
        <div class="container-page max-w-lg">
            <div class="card p-6 sm:p-8">
                <h1 class="font-display text-2xl font-bold sm:text-3xl">{{ $title }}</h1>
                <div class="mt-6 space-y-5">
                    <x-flash />
                    {{ $slot }}
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
