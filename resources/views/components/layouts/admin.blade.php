@props(['title', 'actions' => null])
@php
    $user = auth()->user();
    $groups = [
        'Overview' => [
            ['admin.dashboard', 'Dashboard', 'home', 'dashboard.view', 'admin.dashboard'],
        ],
        'Front desk' => [
            ['admin.appointments.index', 'Appointments', 'calendar', 'appointments.view', 'admin.appointments.*'],
            ['admin.enquiries.index', 'Enquiries', 'inbox', 'enquiries.view', 'admin.enquiries.*'],
            ['admin.customers.index', 'Customers', 'users', 'customers.view', 'admin.customers.*'],
        ],
        'Website content' => [
            ['admin.services.index', 'Services', 'eye', 'services.manage', 'admin.services.*'],
            ['admin.products.index', 'Products', 'glasses', 'products.manage', 'admin.products.*'],
            ['admin.categories.index', 'Product categories', 'tag', 'products.manage', 'admin.categories.*'],
            ['admin.branches.index', 'Branches', 'building', 'branches.manage', 'admin.branches.*'],
            ['admin.articles.index', 'Health articles', 'book', 'articles.manage', 'admin.articles.*'],
        ],
        'Administration' => [
            ['admin.reports.index', 'Reports', 'chart', 'reports.view', 'admin.reports.*'],
            ['admin.staff.index', 'Staff & roles', 'shield', 'staff.manage', 'admin.staff.*'],
            ['admin.settings.edit', 'Settings', 'cog', 'settings.access', 'admin.settings.*'],
            ['admin.activity', 'Activity log', 'activity', 'audit.view', 'admin.activity'],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="en-GH" class="h-full bg-navy-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} | Admin – {{ $site->get('business_name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-navy-50" x-data="{ sidebar: false }" @keydown.escape.window="sidebar = false">
    <a href="#main" class="sr-only z-50 rounded bg-navy px-4 py-2 text-white focus:not-sr-only focus:absolute focus:left-4 focus:top-4">Skip to main content</a>
    <div class="min-h-full lg:flex">
        <div class="fixed inset-0 z-30 bg-navy/50 lg:hidden" x-show="sidebar" x-cloak @click="sidebar = false" aria-hidden="true"></div>
        <aside class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full overflow-y-auto bg-navy text-white transition-transform lg:static lg:translate-x-0"
               :class="sidebar ? 'translate-x-0' : '-translate-x-full'" aria-label="Admin navigation">
            <div class="flex items-center justify-between px-5 py-5">
                <a href="{{ route('admin.dashboard') }}" class="no-underline"><x-logo light /></a>
                <button type="button" class="rounded p-1 lg:hidden" @click="sidebar = false"><x-icon name="x" /><span class="sr-only">Close navigation</span></button>
            </div>
            <nav class="px-3 pb-8">
                @foreach ($groups as $group => $items)
                    @php $visible = array_filter($items, fn ($i) => $user->can($i[3])); @endphp
                    @if ($visible)
                        <p class="mt-5 px-3 text-xs font-semibold uppercase tracking-wider text-white/60">{{ $group }}</p>
                        <ul class="mt-2 space-y-0.5">
                            @foreach ($visible as [$route, $label, $icon, $perm, $match])
                                @php $active = request()->routeIs($match); @endphp
                                <li><a href="{{ route($route) }}" @if ($active) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium no-underline {{ $active ? 'bg-teal text-white' : 'text-white/85 hover:bg-white/10 hover:text-white' }}"><x-icon :name="$icon" class="h-4 w-4" /> {{ $label }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                @endforeach
                <p class="mt-5 px-3 text-xs font-semibold uppercase tracking-wider text-white/60">Website</p>
                <ul class="mt-2 space-y-0.5">
                    <li><a href="{{ route('home') }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-white/85 no-underline hover:bg-white/10"><x-icon name="external" class="h-4 w-4" /> View public site</a></li>
                </ul>
            </nav>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 flex items-center justify-between gap-4 border-b border-navy/10 bg-white px-4 py-3 sm:px-6">
                <button type="button" class="rounded p-2 text-navy lg:hidden" @click="sidebar = true" :aria-expanded="sidebar.toString()" aria-expanded="false"><x-icon name="menu" /><span class="sr-only">Open navigation</span></button>
                <p class="hidden text-sm sm:block">Signed in as <strong class="text-navy">{{ $user->name }}</strong> · {{ $user->roles->pluck('name')->join(', ') }}</p>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.profile.edit') }}" class="text-sm font-semibold">My password</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-secondary btn-sm" type="submit"><x-icon name="logout" class="h-4 w-4" /> Sign out</button></form>
                </div>
            </header>
            <main id="main" tabindex="-1" class="flex-1 px-4 py-8 focus:outline-none sm:px-6 lg:px-10">
                <div class="mx-auto max-w-7xl">
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            @isset($breadcrumb)<div class="mb-1 text-sm">{{ $breadcrumb }}</div>@endisset
                            <h1 class="font-display text-2xl font-bold sm:text-3xl">{{ $title }}</h1>
                        </div>
                        @if ($actions)<div class="flex flex-wrap gap-2">{{ $actions }}</div>@endif
                    </div>
                    <x-flash class="mb-6" />
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>
</html>
