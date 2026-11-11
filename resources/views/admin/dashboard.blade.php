<x-layouts.admin title="Dashboard">
    @if ($stats)
        <section aria-label="Summary" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @isset($stats['pending'])<x-admin.stat-card label="Requests awaiting review" :value="$stats['pending']" icon="clock" :href="route('admin.appointments.index', ['status' => 'pending'])" note="Pending and reschedule requests" />@endisset
            @isset($stats['confirmed_upcoming'])<x-admin.stat-card label="Upcoming confirmed" :value="$stats['confirmed_upcoming']" icon="calendar" :href="route('admin.appointments.index', ['status' => 'confirmed'])" />@endisset
            @isset($stats['new_enquiries'])<x-admin.stat-card label="New enquiries" :value="$stats['new_enquiries']" icon="inbox" :href="route('admin.enquiries.index', ['status' => 'new'])" />@endisset
            @isset($stats['services'])<x-admin.stat-card label="Published services" :value="$stats['services']" icon="eye" :href="route('admin.services.index')" />@endisset
            @isset($stats['products'])<x-admin.stat-card label="Published products" :value="$stats['products']" icon="glasses" :href="route('admin.products.index')" />@endisset
        </section>
    @endif

    <div class="mt-8 grid gap-6 xl:grid-cols-2">
        @isset($awaiting)
            <section class="card p-6" aria-labelledby="awaiting-h">
                <div class="flex items-center justify-between"><h2 id="awaiting-h" class="text-lg font-semibold">Oldest requests awaiting review</h2><a href="{{ route('admin.appointments.index') }}" class="text-sm font-semibold">All</a></div>
                @if ($awaiting->isEmpty())
                    <p class="mt-4 text-sm">Nothing waiting – all requests have been reviewed.</p>
                @else
                    <ul class="mt-4 divide-y divide-navy/10">
                        @foreach ($awaiting as $a)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                                <div>
                                    <a href="{{ route('admin.appointments.show', $a) }}" class="font-semibold">{{ $a->customer_name }}</a>
                                    <p class="text-xs">{{ $a->service->name }} · {{ $a->branch->name }} · requested {{ $a->requestedStart()->format('D j M, H:i') }}</p>
                                </div>
                                <x-status-badge :status="$a->status" size="sm" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endisset
        @isset($todays)
            <section class="card p-6" aria-labelledby="today-h">
                <h2 id="today-h" class="text-lg font-semibold">Today's confirmed appointments</h2>
                @if ($todays->isEmpty())
                    <p class="mt-4 text-sm">No confirmed appointments today.</p>
                @else
                    <ul class="mt-4 divide-y divide-navy/10">
                        @foreach ($todays as $a)
                            <li class="flex items-center justify-between py-3 text-sm"><span><strong class="text-navy">{{ $a->confirmed_start_at->format('H:i') }}</strong> · <a href="{{ route('admin.appointments.show', $a) }}">{{ $a->customer_name }}</a></span><span>{{ $a->branch->name }}</span></li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endisset
        @isset($recentEnquiries)
            <section class="card p-6" aria-labelledby="enq-h">
                <div class="flex items-center justify-between"><h2 id="enq-h" class="text-lg font-semibold">Recent enquiries</h2><a href="{{ route('admin.enquiries.index') }}" class="text-sm font-semibold">All</a></div>
                @if ($recentEnquiries->isEmpty())
                    <p class="mt-4 text-sm">No enquiries yet.</p>
                @else
                    <ul class="mt-4 divide-y divide-navy/10">
                        @foreach ($recentEnquiries as $e)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                                <div><a href="{{ route('admin.enquiries.show', $e) }}" class="font-semibold">{{ $e->subject }}</a><p class="text-xs">{{ $e->name }} · {{ $e->created_at->diffForHumans() }}</p></div>
                                <x-status-badge :status="$e->status" size="sm" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endisset
        @isset($activity)
            <section class="card p-6" aria-labelledby="act-h">
                <div class="flex items-center justify-between"><h2 id="act-h" class="text-lg font-semibold">Recent administrative activity</h2><a href="{{ route('admin.activity') }}" class="text-sm font-semibold">All</a></div>
                @if ($activity->isEmpty())
                    <p class="mt-4 text-sm">No activity recorded yet.</p>
                @else
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach ($activity as $log)
                            <li><span class="font-semibold text-navy">{{ $log->user?->name ?? 'System' }}</span> {{ $log->description() }} <span class="text-xs">· {{ $log->created_at->diffForHumans() }}</span></li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endisset
    </div>

    @if (! $stats && ! isset($activity))
        <x-empty-state title="Welcome" icon="home">Use the navigation to manage the areas your role gives you access to.</x-empty-state>
    @endif
</x-layouts.admin>
