@php
    $shopOn = app(\App\Services\ShopService::class)->enabled();
    $cartCount = $shopOn ? app(\App\Services\CartService::class)->count() : 0;
    $nav = [
        ['label' => 'Home', 'route' => 'home', 'match' => 'home'],
        ['label' => 'About', 'route' => 'about', 'match' => 'about'],
        ['label' => 'Services', 'route' => 'services.index', 'match' => 'services.*'],
        ['label' => 'Frames & Lenses', 'route' => 'products.index', 'match' => 'products.*'],
        ['label' => 'Branches', 'route' => 'branches.index', 'match' => 'branches.*'],
        ['label' => 'Eye Health', 'route' => 'articles.index', 'match' => 'articles.*'],
        ['label' => 'Contact', 'route' => 'contact', 'match' => 'contact'],
    ];
@endphp
<header x-data="{ open: false }" @keydown.escape.window="open = false" class="sticky top-0 z-40 border-b border-navy/10 bg-white/95 backdrop-blur">
    <div class="container-page flex h-18 items-center justify-between gap-4 py-3">
        <a href="{{ route('home') }}" class="no-underline" aria-label="{{ $site->get('business_name') }} – home">
            <x-logo />
        </a>

        <nav aria-label="Main" class="hidden lg:block">
            <ul class="flex items-center gap-1">
                @foreach ($nav as $item)
                    @php $active = request()->routeIs($item['match']); @endphp
                    <li>
                        <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                           class="rounded-md px-3 py-2 text-sm font-semibold no-underline {{ $active ? 'bg-sky text-navy' : 'text-navy/80 hover:bg-sky/60 hover:text-navy' }}">{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="hidden items-center gap-3 lg:flex">
            @if ($shopOn)
                <a href="{{ route('shop.cart') }}" class="btn-text relative text-sm" @if (request()->routeIs('shop.*')) aria-current="page" @endif>
                    <x-icon name="cart" class="h-5 w-5" /><span class="sr-only">Cart,</span>
                    <span class="rounded-full bg-teal px-1.5 text-xs font-bold text-white">{{ $cartCount }}</span><span class="sr-only">items</span>
                </a>
            @endif
            @auth
                <a href="{{ auth()->user()->isStaff() ? route('admin.dashboard') : route('account.index') }}" class="btn-text text-sm">
                    <x-icon name="user" class="h-4 w-4" /> {{ auth()->user()->isStaff() ? 'Dashboard' : 'My account' }}
                </a>
            @else
                <a href="{{ route('login') }}" class="btn-text text-sm">Sign in</a>
            @endauth
            <a href="{{ route('appointments.create') }}" class="btn-primary"><x-icon name="calendar" class="h-4 w-4" /> Book Appointment</a>
        </div>

        @if ($shopOn)
            <a href="{{ route('shop.cart') }}" class="ml-auto inline-flex items-center gap-1 rounded-md p-2 font-semibold text-navy no-underline lg:hidden">
                <x-icon name="cart" class="h-6 w-6" /><span class="sr-only">Cart,</span><span class="rounded-full bg-teal px-1.5 text-xs font-bold text-white">{{ $cartCount }}</span><span class="sr-only">items</span>
            </a>
        @endif
        <button type="button" class="inline-flex items-center gap-2 rounded-md p-2 text-navy lg:hidden" @click="open = !open"
                :aria-expanded="open.toString()" aria-controls="mobile-nav" aria-expanded="false">
            <span class="sr-only" x-text="open ? 'Close menu' : 'Open menu'">Open menu</span>
            <x-icon name="menu" class="h-6 w-6" x-show="!open" />
            <x-icon name="x" class="h-6 w-6" x-show="open" x-cloak />
        </button>
    </div>

    <nav id="mobile-nav" aria-label="Mobile" class="border-t border-navy/10 bg-white lg:hidden" x-show="open" x-cloak x-transition.opacity>
        <ul class="container-page space-y-1 py-4">
            @foreach ($nav as $item)
                @php $active = request()->routeIs($item['match']); @endphp
                <li><a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif class="block rounded-md px-3 py-2.5 font-semibold no-underline {{ $active ? 'bg-sky text-navy' : 'text-navy hover:bg-sky/60' }}">{{ $item['label'] }}</a></li>
            @endforeach
            <li class="border-t border-navy/10 pt-3">
                @auth
                    <a href="{{ auth()->user()->isStaff() ? route('admin.dashboard') : route('account.index') }}" class="block px-3 py-2 font-semibold no-underline">{{ auth()->user()->isStaff() ? 'Dashboard' : 'My account' }}</a>
                @else
                    <a href="{{ route('login') }}" class="block px-3 py-2 font-semibold no-underline">Sign in</a>
                @endauth
            </li>
            <li><a href="{{ route('appointments.create') }}" class="btn-primary mt-2 w-full">Book Appointment</a></li>
        </ul>
    </nav>
</header>
