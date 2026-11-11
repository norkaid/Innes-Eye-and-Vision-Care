<x-layouts.admin :title="$branch->exists ? 'Edit branch' : 'New branch'">
    <x-slot:breadcrumb><a href="{{ route('admin.branches.index') }}">Branches</a></x-slot:breadcrumb>
    <form method="POST" action="{{ $branch->exists ? route('admin.branches.update', $branch) : route('admin.branches.store') }}" class="grid gap-6 xl:grid-cols-3" novalidate>
        @csrf
        @if ($branch->exists) @method('PUT') @endif
        <div class="space-y-6 xl:col-span-2">
            <div class="card space-y-5 p-6">
                <h2 class="font-semibold">Details</h2>
                <x-alert type="warning" title="Verified information only">Publish only addresses, phone numbers and hours confirmed by the centre.</x-alert>
                <x-form.input name="name" label="Branch name" required :value="$branch->name" />
                <x-form.input name="slug" label="URL slug" :value="$branch->slug" hint="Leave blank to generate from the name." />
                <x-form.input name="address" label="Street address" :value="$branch->address" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="city" label="City / town" :value="$branch->city" />
                    <x-form.input name="region" label="Region" :value="$branch->region" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="phone" type="tel" label="Phone" :value="$branch->phone" />
                    <x-form.input name="email" type="email" label="Email" :value="$branch->email" />
                </div>
                <x-form.input name="map_url" type="url" label="Map / directions link" :value="$branch->map_url" hint="e.g. a Google Maps share link (https://…)." />
                <x-form.textarea name="description" label="Description" rows="3" :value="$branch->description" />
            </div>
            <fieldset class="card p-6">
                <legend class="sr-only">Opening hours</legend>
                <h2 class="font-semibold" aria-hidden="true">Opening hours</h2>
                <p class="text-sm">Online booking times are generated from these hours.</p>
                <div class="mt-4 divide-y divide-navy/10">
                    @foreach (\App\Models\Branch::DAYS as $day => $dayName)
                        @php $h = $hours[$day] ?? null; $closed = old("hours.$day.is_closed", $h ? $h->is_closed : ($day === 7)); @endphp
                        <div class="grid items-center gap-3 py-3 sm:grid-cols-[120px_1fr_1fr_auto]" x-data="{ closed: {{ $closed ? 'true' : 'false' }} }">
                            <span class="font-semibold text-navy">{{ $dayName }}</span>
                            <div><label for="h-{{ $day }}-o" class="sr-only">{{ $dayName }} opens</label><input id="h-{{ $day }}-o" type="time" name="hours[{{ $day }}][opens_at]" value="{{ old("hours.$day.opens_at", $h?->opens_at ? substr($h->opens_at, 0, 5) : '08:30') }}" class="form-input" :disabled="closed"></div>
                            <div><label for="h-{{ $day }}-c" class="sr-only">{{ $dayName }} closes</label><input id="h-{{ $day }}-c" type="time" name="hours[{{ $day }}][closes_at]" value="{{ old("hours.$day.closes_at", $h?->closes_at ? substr($h->closes_at, 0, 5) : '17:00') }}" class="form-input" :disabled="closed"></div>
                            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="hours[{{ $day }}][is_closed]" value="1" x-model="closed" @checked($closed) class="rounded text-teal focus:ring-teal"> Closed</label>
                            @error("hours.$day.closes_at")<p class="form-error sm:col-span-4">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            </fieldset>
        </div>
        <div class="space-y-6">
            <div class="card space-y-4 p-6">
                <h2 class="font-semibold">Visibility</h2>
                <x-form.checkbox name="is_active" label="Active (shown on website and booking form)" :checked="(bool) $branch->is_active" />
                <x-form.input name="display_order" type="number" min="0" label="Display order" :value="$branch->display_order ?? 0" />
            </div>
            <fieldset class="card space-y-3 p-6">
                <legend class="sr-only">Services at this branch</legend>
                <h2 class="font-semibold" aria-hidden="true">Services offered</h2>
                @forelse ($services as $s)
                    <x-form.checkbox name="services[]" :value="$s->id" :label="$s->name.($s->is_published ? '' : ' (draft)')" :checked="in_array($s->id, $selected)" />
                @empty
                    <p class="text-sm">No services yet.</p>
                @endforelse
            </fieldset>
            <div class="flex gap-3"><button class="btn-primary" type="submit">Save branch</button><a href="{{ route('admin.branches.index') }}" class="btn-secondary">Cancel</a></div>
        </div>
    </form>
</x-layouts.admin>
