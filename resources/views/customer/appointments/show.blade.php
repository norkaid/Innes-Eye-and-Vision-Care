<x-layouts.account title="Request {{ $appointment->reference_code }}">
    <div class="card p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold">{{ $appointment->service->name }}</h2>
            <x-status-badge :status="$appointment->status" />
        </div>
        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
            <div><dt class="font-semibold text-navy">Branch</dt><dd>{{ $appointment->branch->name }}</dd></div>
            <div><dt class="font-semibold text-navy">Requested time</dt><dd>{{ $appointment->requestedStart()->format('l j F Y, H:i') }}</dd></div>
            @if ($appointment->confirmed_start_at)
                <div><dt class="font-semibold text-navy">Confirmed time</dt><dd class="font-semibold text-teal-700">{{ $appointment->confirmed_start_at->format('l j F Y, H:i') }}</dd></div>
            @endif
            @if ($appointment->proposed_start_at)
                <div><dt class="font-semibold text-navy">Time proposed by the clinic</dt><dd>{{ $appointment->proposed_start_at->format('l j F Y, H:i') }} – please contact the branch to accept or arrange another time.</dd></div>
            @endif
            @if ($appointment->customer_note)<div class="sm:col-span-2"><dt class="font-semibold text-navy">Your note</dt><dd>{{ $appointment->customer_note }}</dd></div>@endif
        </dl>
        @if ($appointment->status === \App\Enums\AppointmentStatus::Pending)
            <x-alert type="info" title="Awaiting review" class="mt-6">This request is not yet confirmed. The clinic will contact you.</x-alert>
        @endif
    </div>

    @if ($appointment->status->isOpen())
        @if ($beforeCutoff)
            <div class="grid gap-6 md:grid-cols-2">
                @if ($appointment->status->customerCanRequestReschedule())
                    <form method="POST" action="{{ route('account.appointments.reschedule', $appointment) }}" class="card space-y-4 p-6" novalidate
                          x-data="bookingForm({ endpoint: @js(route('appointments.availability')), branch: @js((string) $appointment->branch_id), date: @js(old('requested_date')), time: @js(old('requested_time')) })">
                        @csrf
                        <h2 class="text-lg font-semibold">Request a different time</h2>
                        <x-form.input name="requested_date" type="date" label="New preferred date" required min="{{ $minDate }}" max="{{ $maxDate }}" x-model="date" x-on:change="loadSlots()" />
                        <div>
                            <span class="form-label" id="rs-time">New preferred time</span>
                            <input type="hidden" name="requested_time" :value="time">
                            <p class="text-sm" x-show="loading" x-cloak role="status">Loading times…</p>
                            <p class="text-sm" x-show="!loading && message" x-text="message" x-cloak role="status"></p>
                            <div role="radiogroup" aria-labelledby="rs-time" class="grid grid-cols-3 gap-2" x-show="slots.length" x-cloak>
                                <template x-for="slot in slots" :key="slot">
                                    <button type="button" role="radio" :aria-checked="(time === slot).toString()" @click="time = slot" class="rounded-lg border px-2 py-2 text-sm font-semibold" :class="time === slot ? 'border-teal bg-teal text-white' : 'border-navy/20 text-navy'"><span x-text="slot"></span></button>
                                </template>
                            </div>
                            @error('requested_time')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <button class="btn-primary" type="submit">Send reschedule request</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('account.appointments.cancel', $appointment) }}" class="card space-y-4 p-6" x-data @submit="if (!confirm('Cancel this appointment request?')) $event.preventDefault()">
                    @csrf
                    <h2 class="text-lg font-semibold">Cancel this request</h2>
                    <p class="text-sm">If you no longer need this appointment, please let us know so the time can be offered to someone else.</p>
                    <button class="btn-danger" type="submit">Cancel request</button>
                </form>
            </div>
        @else
            <x-alert type="warning" title="Changes need a phone call">This appointment is within {{ $site->int('cancellation_cutoff_hours') }} hours. Please contact {{ $appointment->branch->name }} directly to make changes.</x-alert>
        @endif
    @endif
    <a href="{{ route('account.appointments.index') }}" class="btn-text text-sm"><x-icon name="chevron-left" class="h-4 w-4" /> All requests</a>
</x-layouts.account>
