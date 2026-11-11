@props(['title' => null, 'description' => null, 'image' => null, 'noindex' => false])
<!DOCTYPE html>
<html lang="en-GH" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#123047">
    <x-seo :title="$title" :description="$description" :image="$image" :noindex="$noindex" />
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>
<body class="flex min-h-full flex-col">
    <a href="#main" class="sr-only z-50 rounded bg-navy px-4 py-2 text-white focus:not-sr-only focus:absolute focus:left-4 focus:top-4">Skip to main content</a>
    <x-demo-banner />
    <x-site-header />
    <main id="main" tabindex="-1" class="flex-1 focus:outline-none">
        {{ $slot }}
    </main>
    <x-site-footer />
</body>
</html>
