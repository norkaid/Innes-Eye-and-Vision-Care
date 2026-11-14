<x-layouts.admin title="Settings">
    <div class="space-y-8">
        @can('settings.general')
            <form method="POST" action="{{ route('admin.settings.update') }}" class="card space-y-5 p-6" novalidate>
                @csrf @method('PUT')
                <input type="hidden" name="section" value="general">
                <h2 class="text-lg font-semibold">Business details &amp; About page</h2>
                <p class="text-sm">Shown publicly. Enter only verified, business-approved information.</p>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="business_name" label="Public business name" required :value="$values['business_name']" />
                    <x-form.input name="tagline" label="Tagline" :value="$values['tagline']" />
                    <x-form.input name="contact_phone" type="tel" label="General phone" :value="$values['contact_phone']" />
                    <x-form.input name="contact_email" type="email" label="General email" :value="$values['contact_email']" />
                    <x-form.input name="facebook_url" type="url" label="Facebook page URL" :value="$values['facebook_url']" />
                    <x-form.input name="instagram_url" type="url" label="Instagram URL" :value="$values['instagram_url']" />
                    <x-form.input name="whatsapp" label="WhatsApp number" :value="$values['whatsapp']" />
                    <x-form.input name="response_time_note" label="Response-time note (contact page)" :value="$values['response_time_note']" />
                </div>
                <x-form.textarea name="about_intro" label="Company background (About page & home intro)" rows="5" :value="$values['about_intro']" />
                <div class="grid gap-5 md:grid-cols-3">
                    <x-form.textarea name="mission" label="Mission" rows="3" :value="$values['mission']" />
                    <x-form.textarea name="vision" label="Vision" rows="3" :value="$values['vision']" />
                    <x-form.textarea name="values" label="Values" rows="3" :value="$values['values']" />
                </div>
                <button class="btn-primary" type="submit">Save business details</button>
            </form>
        @endcan

        @can('settings.general')
            @php
                $logos = \App\Models\Media::where('folder', 'logo')->latest()->limit(30)->get();
            @endphp
            <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="card space-y-5 p-6" novalidate>
                @csrf @method('PUT')
                <input type="hidden" name="section" value="logo">
                <h2 class="text-lg font-semibold">Logo</h2>
                <p class="text-sm">Shown in the website header and footer and in the admin sidebar. A PNG with a transparent background, about 400 px wide, works best.</p>
                <div class="flex flex-wrap items-center gap-6">
                    <div class="rounded-lg border border-navy/10 bg-white p-4"><x-logo /></div>
                    <div class="rounded-lg bg-navy p-4"><x-logo light /></div>
                </div>
                <div>
                    <label for="f-logo" class="form-label">Upload a new logo</label>
                    <input id="f-logo" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        class="block w-full text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-navy file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-navy-700">
                    <p class="form-hint">JPG, PNG or WebP. It is saved in the “Logo &amp; branding” folder of the media library.</p>
                    @error('logo')<p class="form-error"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" /> {{ $message }}</p>@enderror
                </div>
                @if ($logos->isNotEmpty())
                    <fieldset>
                        <legend class="form-label">Or choose one from the “Logo &amp; branding” folder</legend>
                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-6">
                            @foreach ($logos as $m)
                                <label class="cursor-pointer overflow-hidden rounded-lg border border-navy/15 p-2 has-[:checked]:border-teal has-[:checked]:ring-2 has-[:checked]:ring-teal" title="{{ $m->original_name }}">
                                    <input type="radio" name="logo_media_id" value="{{ $m->id }}" class="sr-only" @checked(($values['logo_path'] ?? null) === $m->path)>
                                    <img src="{{ $m->url() }}" alt="{{ $m->alt_text ?: $m->original_name }}" class="aspect-[3/2] w-full object-contain">
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif
                @if ($values['logo_path'] ?? null)
                    <x-form.checkbox name="remove_logo" label="Stop using the uploaded logo (show the standard text logo)" />
                @endif
                <button class="btn-primary" type="submit">Save logo</button>
            </form>
        @endcan

        @can('settings.system')
            <form method="POST" action="{{ route('admin.settings.update') }}" class="card space-y-5 p-6" novalidate>
                @csrf @method('PUT')
                <input type="hidden" name="section" value="system">
                <h2 class="text-lg font-semibold">Appointment rules &amp; notifications</h2>
                <p class="text-sm">These rules control which times are offered online and how confirmations are checked. Agree them with the clinic before changing.</p>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <x-form.input name="booking_lead_time_hours" type="number" min="0" label="Minimum notice (hours)" required :value="$values['booking_lead_time_hours']" hint="Earliest a request can be for." />
                    <x-form.input name="booking_window_days" type="number" min="1" label="Booking window (days ahead)" required :value="$values['booking_window_days']" />
                    <x-form.select name="slot_duration_minutes" label="Appointment slot length" required :options="collect([10, 15, 20, 30, 45, 60, 90, 120])->mapWithKeys(fn ($m) => [$m => $m.' minutes'])->all()" :value="$values['slot_duration_minutes']" />
                    <x-form.input name="slot_capacity" type="number" min="1" label="Confirmed appointments per slot (per branch)" required :value="$values['slot_capacity']" hint="e.g. number of examination rooms." />
                    <x-form.input name="cancellation_cutoff_hours" type="number" min="0" label="Online change cut-off (hours before)" required :value="$values['cancellation_cutoff_hours']" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.checkbox name="notify_customer_emails" label="Send customer acknowledgement and status emails" :checked="filter_var($values['notify_customer_emails'], FILTER_VALIDATE_BOOL)" hint="Requires a working mail configuration (MAIL_* settings)." />
                    <x-form.input name="notify_staff_email" type="email" label="Staff alert email" :value="$values['notify_staff_email']" hint="Receives an alert (without personal details) for new requests and enquiries." />
                </div>
                <p class="text-sm">Current mail driver: <strong class="text-navy">{{ config('mail.default') }}</strong>@if (config('mail.default') === 'log') (development – messages are written to the log file, not delivered)@endif</p>
                <button class="btn-primary" type="submit">Save rules</button>
            </form>
        @endcan
    </div>
</x-layouts.admin>
