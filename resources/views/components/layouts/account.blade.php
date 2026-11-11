@props(['title'])
<x-layouts.public :title="$title" :noindex="true">
    <section class="bg-sky/50">
        <div class="container-page py-8">
            <p class="text-sm font-semibold uppercase tracking-wider text-teal-700">My account</p>
            <h1 class="font-display text-3xl font-bold">{{ $title }}</h1>
        </div>
    </section>
    <div class="container-page grid gap-8 py-10 lg:grid-cols-[220px_1fr]">
        <nav aria-label="Account">
            <ul class="flex flex-wrap gap-1 lg:flex-col">
                @foreach ([['account.index', 'Overview', 'home'], ['account.appointments.index', 'Appointment requests', 'calendar'], ['account.enquiries', 'My enquiries', 'inbox'], ['account.profile', 'Profile & password', 'user']] as [$r, $label, $icon])
                    @php $active = request()->routeIs($r === 'account.appointments.index' ? 'account.appointments.*' : $r); @endphp
                    <li><a href="{{ route($r) }}" @if ($active) aria-current="page" @endif class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold no-underline {{ $active ? 'bg-navy text-white' : 'text-navy hover:bg-sky' }}"><x-icon :name="$icon" class="h-4 w-4" /> {{ $label }}</a></li>
                @endforeach
                <li>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm font-semibold text-navy hover:bg-sky"><x-icon name="logout" class="h-4 w-4" /> Sign out</button></form>
                </li>
            </ul>
        </nav>
        <div class="min-w-0 space-y-6">
            <x-flash />
            {{ $slot }}
        </div>
    </div>
</x-layouts.public>
