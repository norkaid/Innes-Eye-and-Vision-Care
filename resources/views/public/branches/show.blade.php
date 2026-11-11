<x-layouts.public :title="$branch->name" :description="'Address, opening hours and services at '.$branch->name.'.'">
    <x-page-header :title="$branch->name" :intro="collect([$branch->city, $branch->region])->filter()->join(', ')">
        <x-slot:breadcrumbs><x-breadcrumbs :items="['Branches' => route('branches.index'), $branch->name => null]" /></x-slot:breadcrumbs>
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('appointments.create', ['branch' => $branch->slug]) }}" class="btn-primary"><x-icon name="calendar" class="h-4 w-4" /> Book at this branch</a>
            @if ($branch->directionsUrl())<a href="{{ $branch->directionsUrl() }}" class="btn-secondary" target="_blank" rel="noopener">Get directions <x-icon name="external" class="h-4 w-4" /><span class="sr-only">(opens in a new tab)</span></a>@endif
        </div>
    </x-page-header>

    <section class="container-page grid gap-10 py-14 lg:grid-cols-3">
        <div class="space-y-8 lg:col-span-2">
            @if ($branch->description)<div class="prose-content">{!! nl2br(e($branch->description)) !!}</div>@endif
            <div>
                <h2 class="font-display text-2xl font-bold">Services at this branch</h2>
                @if ($branch->services->isNotEmpty())
                    <div class="mt-6 grid gap-6 sm:grid-cols-2">
                        @foreach ($branch->services as $service)<x-service-card :service="$service" />@endforeach
                    </div>
                @else
                    <p class="mt-3">Please contact the branch to ask about available services.</p>
                @endif
            </div>
        </div>
        <aside class="space-y-6">
            <div class="card p-6">
                <h2 class="text-lg font-semibold">Contact</h2>
                <dl class="mt-3 space-y-3 text-sm">
                    @if ($branch->address)<div><dt class="font-semibold text-navy">Address</dt><dd>{{ $branch->address }}</dd></div>@endif
                    @if ($branch->phone)<div><dt class="font-semibold text-navy">Phone</dt><dd><a href="tel:{{ preg_replace('/[^+\d]/', '', $branch->phone) }}">{{ $branch->phone }}</a></dd></div>@endif
                    @if ($branch->email)<div><dt class="font-semibold text-navy">Email</dt><dd><a href="mailto:{{ $branch->email }}">{{ $branch->email }}</a></dd></div>@endif
                </dl>
            </div>
            <div class="card p-6">
                <h2 class="text-lg font-semibold">Opening hours</h2>
                @if ($branch->openingHours->isNotEmpty())
                    <table class="mt-3 w-full text-sm">
                        <caption class="sr-only">Opening hours for {{ $branch->name }}</caption>
                        <tbody>
                            @foreach ($branch->openingHours as $h)
                                <tr class="{{ now()->dayOfWeekIso === $h->day_of_week ? 'font-semibold text-navy' : '' }}">
                                    <th scope="row" class="py-1.5 text-left font-medium">{{ $h->dayName() }}@if (now()->dayOfWeekIso === $h->day_of_week) <span class="text-xs text-teal-700">(today)</span>@endif</th>
                                    <td class="py-1.5 text-right">{{ $h->display() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="mt-2 text-sm">Opening hours to be confirmed. Please call before visiting.</p>
                @endif
            </div>
        </aside>
    </section>
</x-layouts.public>
