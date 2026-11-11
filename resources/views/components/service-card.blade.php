@props(['service'])
<article class="card group relative flex flex-col overflow-hidden transition-shadow hover:shadow-md">
    <img src="{{ \App\Services\ImageUploader::url($service->image_path) ?? asset('images/placeholder-service.svg') }}" alt="" loading="lazy" class="aspect-[16/10] w-full object-cover" width="400" height="250">
    <div class="flex flex-1 flex-col p-6">
        <h3 class="text-lg font-semibold">
            <a href="{{ route('services.show', $service->slug) }}" class="text-navy no-underline after:absolute after:inset-0 group-hover:underline">{{ $service->name }}</a>
        </h3>
        <p class="mt-2 flex-1 text-sm leading-6">{{ $service->short_description }}</p>
        <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-teal-700">Learn more <x-icon name="arrow-right" class="h-4 w-4" /></span>
    </div>
</article>
