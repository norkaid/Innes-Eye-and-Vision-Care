@props(['title', 'intro' => null, 'eyebrow' => null, 'tone' => 'sky'])
<section class="{{ $tone === 'sand' ? 'bg-sand' : 'bg-sky' }}">
    <div class="container-page py-12 sm:py-16">
        @isset($breadcrumbs)
            <nav aria-label="Breadcrumb" class="mb-4 text-sm">{{ $breadcrumbs }}</nav>
        @endisset
        @if ($eyebrow)
            <p class="text-sm font-semibold uppercase tracking-wider text-teal-700">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-1 font-display text-3xl font-bold sm:text-4xl lg:text-5xl">{{ $title }}</h1>
        @if ($intro)
            <p class="mt-4 max-w-3xl text-lg leading-8">{{ $intro }}</p>
        @endif
        {{ $slot }}
    </div>
</section>
