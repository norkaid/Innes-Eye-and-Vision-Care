<x-layouts.public title="Book an appointment" description="Request an eye-care appointment online at {{ $site->get('business_name') }}. Our team will contact you to confirm.">
    <x-page-header title="Request an appointment" eyebrow="Book Appointment" intro="Choose a branch, service and a preferred time. This sends a request – our team will review it and contact you to confirm." />

    <section class="container-page py-12">
        <div class="grid gap-10 lg:grid-cols-3">
            <form method="POST" action="{{ route('appointments.store') }}" novalidate class="card relative space-y-8 p-6 sm:p-8 lg:col-span-2"
                  x-data="bookingForm({
                      endpoint: @js(route('appointments.availability')),
                      branch: @js((string) old('branch_id', $preBranch?->id)),
                      service: @js((string) old('service_id', $preService?->id)),
                      date: @js(old('requested_date')),
                      time: @js(old('requested_time')),
                      branchServices: @js($branchServices),
                  })">
                @csrf
                <x-form.honeypot />
                <x-flash />

                @if ($branches->isEmpty() || $services->isEmpty())
                    <x-alert type="warning" title="Online booking is not available yet">Please <a href="{{ route('contact') }}">contact us</a> to arrange an appointment.</x-alert>
                @else
                <fieldset class="space-y-5">
                    <legend class="text-lg font-semibold text-navy">1. Where and what</legend>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.select name="branch_id" label="Preferred branch" required placeholder="Choose a branch"
                            :options="$branches->pluck('name', 'id')->all()" :value="$preBranch?->id"
                            x-model="branch" x-on:change="onBranchChange()" />
                        <div>
                            <x-form.select name="service_id" label="Service" required placeholder="Choose a service" x-model="service" :options="[]" hint="Only services offered at the chosen branch can be selected.">
                                @foreach ($services as $s)
                                    <option value="{{ $s->id }}" @selected((string) old('service_id', $preService?->id) === (string) $s->id) :disabled="!serviceAvailable({{ $s->id }})">{{ $s->name }}</option>
                                @endforeach
                            </x-form.select>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="space-y-5">
                    <legend class="text-lg font-semibold text-navy">2. When</legend>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.input name="requested_date" type="date" label="Preferred date" required min="{{ $minDate }}" max="{{ $maxDate }}"
                            x-model="date" x-on:change="loadSlots()" hint="Requests can be made up to {{ $windowDays }} days ahead, subject to branch opening days." />
                        <div>
                            <span class="form-label" id="time-label">Preferred time <span class="text-red-700" aria-hidden="true">*</span><span class="sr-only">(required)</span></span>
                            <input type="hidden" name="requested_time" :value="time" value="{{ old('requested_time') }}">
                            <div role="radiogroup" aria-labelledby="time-label" @if ($errors->has('requested_time')) aria-describedby="time-error" aria-invalid="true" @endif>
                                <p class="text-sm" x-show="!branch || !date">Choose a branch and date to see available times.</p>
                                <p class="text-sm" x-show="loading" x-cloak role="status"><x-icon name="clock" class="inline h-4 w-4 animate-spin" /> Loading available times…</p>
                                <p class="text-sm font-medium text-amber-ink" x-show="!loading && message" x-text="message" x-cloak role="status"></p>
                                <div class="grid grid-cols-3 gap-2 sm:grid-cols-4" x-show="!loading && slots.length" x-cloak>
                                    <template x-for="slot in slots" :key="slot">
                                        <button type="button" role="radio" :aria-checked="(time === slot).toString()" @click="time = slot"
                                            class="rounded-lg border px-2 py-2 text-sm font-semibold"
                                            :class="time === slot ? 'border-teal bg-teal text-white' : 'border-navy/20 bg-white text-navy hover:border-teal'">
                                            <span x-text="slot"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            @error('requested_time')<p id="time-error" class="form-error"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" /> {{ $message }}</p>@enderror
                            <noscript><p class="form-hint">Times are shown with JavaScript enabled. Alternatively <a href="{{ route('contact') }}">contact us</a>.</p></noscript>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="space-y-5">
                    <legend class="text-lg font-semibold text-navy">3. Your details</legend>
                    <x-form.input name="customer_name" label="Full name" required autocomplete="name" :value="$user?->name" />
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.input name="customer_email" type="email" label="Email address" required autocomplete="email" :value="$user?->email" />
                        <x-form.input name="customer_phone" type="tel" label="Phone number" required autocomplete="tel" :value="$user?->phone" hint="We may call to confirm your appointment." />
                    </div>
                    <x-form.textarea name="customer_note" label="Short note" rows="3" maxlength="500"
                        hint="Maximum 500 characters. Please do NOT include medical details such as diagnoses, medication or medical history – your optometrist will discuss these with you in person." />
                </fieldset>

                <x-form.checkbox name="privacy_consent" label="I agree to the centre using these details to handle my request." :hint="null" />
                <p class="-mt-6 pl-7 text-sm">Read our <a href="{{ route('privacy') }}">privacy notice</a>.</p>

                <div class="flex flex-col gap-3 border-t border-navy/10 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm"><x-icon name="info" class="inline h-4 w-4" /> Submitting this form does <strong>not</strong> confirm an appointment.</p>
                    <button type="submit" class="btn-primary px-6 py-3 text-base">Send appointment request</button>
                </div>
                @endif
            </form>

            <aside class="space-y-6">
                <div class="rounded-2xl bg-sky p-6">
                    <h2 class="text-lg font-semibold">What happens next?</h2>
                    <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm leading-6">
                        <li>You receive a reference number for your request.</li>
                        <li>Our team reviews it against the branch schedule.</li>
                        <li>We contact you to confirm the time, or suggest another.</li>
                    </ol>
                </div>
                @if (config('innes.features.customer_accounts') && ! auth()->check())
                    <div class="card p-6">
                        <h2 class="text-lg font-semibold">Track your requests</h2>
                        <p class="mt-2 text-sm">An account is optional. <a href="{{ route('login') }}">Sign in</a> or <a href="{{ route('register') }}">create an account</a> to see the status of your requests.</p>
                    </div>
                @endif
                <div class="card p-6">
                    <h2 class="text-lg font-semibold">Urgent eye problem?</h2>
                    <p class="mt-2 text-sm">Online requests are not monitored around the clock. For sudden vision loss, eye injury or severe pain, seek urgent medical care.</p>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.public>
