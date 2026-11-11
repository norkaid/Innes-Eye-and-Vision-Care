<x-layouts.admin :title="'Request '.$appointment->reference_code">
    <x-slot:breadcrumb><a href="{{ route('admin.appointments.index') }}">Appointments</a> / {{ $appointment->reference_code }}</x-slot:breadcrumb>
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">Request details</h2>
                    <x-status-badge :status="$appointment->status" />
                </div>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="font-semibold text-navy">Customer</dt><dd>{{ $appointment->customer_name }}@if ($appointment->user) <span class="text-xs">(registered account) @can('customers.view')<a href="{{ route('admin.customers.show', $appointment->user) }}">view</a>@endcan</span>@endif</dd></div>
                    <div><dt class="font-semibold text-navy">Contact</dt><dd><a href="tel:{{ preg_replace('/[^+\d]/', '', $appointment->customer_phone) }}">{{ $appointment->customer_phone }}</a><br><a href="mailto:{{ $appointment->customer_email }}">{{ $appointment->customer_email }}</a></dd></div>
                    <div><dt class="font-semibold text-navy">Service</dt><dd>{{ $appointment->service->name }}</dd></div>
                    <div><dt class="font-semibold text-navy">Branch</dt><dd>{{ $appointment->branch->name }}</dd></div>
                    <div><dt class="font-semibold text-navy">Requested time</dt><dd>{{ $appointment->requestedStart()->format('l j F Y, H:i') }}</dd></div>
                    <div><dt class="font-semibold text-navy">Confirmed time</dt><dd>{{ $appointment->confirmed_start_at ? $appointment->confirmed_start_at->format('l j F Y, H:i').' – '.$appointment->confirmed_end_at->format('H:i') : 'Not confirmed' }}</dd></div>
                    @if ($appointment->proposed_start_at)<div><dt class="font-semibold text-navy">Proposed time</dt><dd>{{ $appointment->proposed_start_at->format('l j F Y, H:i') }}</dd></div>@endif
                    <div><dt class="font-semibold text-navy">Submitted</dt><dd>{{ $appointment->created_at->format('j M Y, H:i') }}</dd></div>
                    <div class="sm:col-span-2"><dt class="font-semibold text-navy">Customer note</dt><dd class="whitespace-pre-line">{{ $appointment->customer_note ?: '—' }}</dd></div>
                </dl>
            </section>

            @can('appointments.manage')
                @if ($transitions)
                    <section class="card p-6" x-data="{ status: @js(old('status', '')) }" aria-labelledby="action-h">
                        <h2 id="action-h" class="text-lg font-semibold">Update status</h2>
                        <form method="GET" action="{{ route('admin.appointments.show', $appointment) }}" class="mt-4 flex flex-wrap items-end gap-3 rounded-lg bg-sky/50 p-4">
                            <div><label for="slot-date" class="form-label">Check availability on</label><input id="slot-date" type="date" name="date" value="{{ $slotDate }}" class="form-input"></div>
                            <button class="btn-secondary btn-sm" type="submit">Show free times</button>
                            <p class="w-full text-sm">Free times at {{ $appointment->branch->name }} on {{ \Illuminate\Support\Carbon::parse($slotDate)->format('D j M Y') }}:
                                @if ($slots) <strong class="text-navy">{{ implode(', ', $slots) }}</strong> @else <strong>none (closed, fully booked or outside the booking window)</strong> @endif
                            </p>
                        </form>
                        <form method="POST" action="{{ route('admin.appointments.update', $appointment) }}" class="mt-5 space-y-4">
                            @csrf @method('PATCH')
                            <fieldset>
                                <legend class="form-label">New status</legend>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($transitions as $t)
                                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-navy/20 px-3 py-2 text-sm font-semibold has-[:checked]:border-teal has-[:checked]:bg-teal-50">
                                            <input type="radio" name="status" value="{{ $t->value }}" x-model="status" class="text-teal focus:ring-teal" required> {{ $t === \App\Enums\AppointmentStatus::RescheduleProposed ? 'Propose a new time' : $t->label() }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('status')<p class="form-error">{{ $message }}</p>@enderror
                            </fieldset>
                            <div class="grid gap-4 sm:grid-cols-2" x-show="status === 'confirmed' || status === 'reschedule_proposed'" x-cloak>
                                <x-form.input name="start_date" type="date" label="Date" :value="$slotDate" />
                                <x-form.input name="start_time" type="time" label="Start time" :value="$appointment->requested_time ? substr($appointment->requested_time, 0, 5) : ''" step="300" hint="Confirmations are checked against opening hours and branch capacity." />
                            </div>
                            <x-form.textarea name="note" label="Internal note for the history (staff only)" rows="2" />
                            <x-form.checkbox name="notify" label="Email the customer about this change (if email is configured)" :checked="true" />
                            <button type="submit" class="btn-primary">Save status</button>
                        </form>
                    </section>
                @else
                    <x-alert type="info" title="Closed request">This request is {{ strtolower($appointment->status->label()) }} and cannot be changed further.</x-alert>
                @endif
            @endcan
        </div>

        <div class="space-y-6">
            <section class="card p-6" aria-labelledby="note-h">
                <h2 id="note-h" class="text-lg font-semibold">Internal notes</h2>
                <p class="text-xs">Private to staff. Never shown to the customer. Do not record clinical information.</p>
                @can('appointments.manage')
                    <form method="POST" action="{{ route('admin.appointments.note', $appointment) }}" class="mt-3 space-y-3">
                        @csrf @method('PATCH')
                        <label for="staff_note" class="sr-only">Internal note</label>
                        <textarea id="staff_note" name="staff_note" rows="5" class="form-input">{{ old('staff_note', $appointment->staff_note) }}</textarea>
                        <button class="btn-navy btn-sm" type="submit">Save note</button>
                    </form>
                @else
                    <p class="mt-3 whitespace-pre-line text-sm">{{ $appointment->staff_note ?: 'No notes.' }}</p>
                @endcan
            </section>
            <section class="card p-6" aria-labelledby="hist-h">
                <h2 id="hist-h" class="text-lg font-semibold">Status history</h2>
                <ol class="mt-4 space-y-4 border-l-2 border-sky pl-4">
                    @foreach ($appointment->histories as $h)
                        <li class="text-sm">
                            <p class="font-semibold text-navy">{{ $h->old_status ? $h->old_status->label().' → ' : '' }}{{ $h->new_status->label() }}</p>
                            <p class="text-xs">{{ $h->created_at->format('j M Y, H:i') }} · {{ $h->changer?->name ?? 'Customer / system' }}</p>
                            @if ($h->internal_note)<p class="mt-1 whitespace-pre-line">{{ $h->internal_note }}</p>@endif
                        </li>
                    @endforeach
                </ol>
                @if ($appointment->handler)<p class="mt-4 text-xs">Last handled by {{ $appointment->handler->name }}</p>@endif
            </section>
        </div>
    </div>
</x-layouts.admin>
