<x-layouts.admin :title="$enquiry->subject">
    <x-slot:breadcrumb><a href="{{ route('admin.enquiries.index') }}">Enquiries</a> / #{{ $enquiry->id }}</x-slot:breadcrumb>
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card p-6">
                <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg font-semibold">Message</h2><x-status-badge :status="$enquiry->status" /></div>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                    <div><dt class="font-semibold text-navy">From</dt><dd>{{ $enquiry->name }}</dd></div>
                    <div><dt class="font-semibold text-navy">Email</dt><dd><a href="mailto:{{ $enquiry->email }}?subject={{ rawurlencode('Re: '.$enquiry->subject) }}">{{ $enquiry->email }}</a></dd></div>
                    <div><dt class="font-semibold text-navy">Phone</dt><dd>{{ $enquiry->phone ?: '—' }}</dd></div>
                    <div><dt class="font-semibold text-navy">Received</dt><dd>{{ $enquiry->created_at->format('j M Y, H:i') }}</dd></div>
                    @if ($enquiry->product)<div><dt class="font-semibold text-navy">Product</dt><dd>{{ $enquiry->product->name }}</dd></div>@endif
                </dl>
                <div class="mt-6 whitespace-pre-line rounded-lg bg-navy-50 p-4 text-sm text-navy">{{ $enquiry->message }}</div>
                <a href="mailto:{{ $enquiry->email }}?subject={{ rawurlencode('Re: '.$enquiry->subject) }}" class="btn-secondary btn-sm mt-4"><x-icon name="mail" class="h-4 w-4" /> Reply by email</a>
            </section>
            <section class="card p-6" aria-labelledby="notes-h">
                <h2 id="notes-h" class="text-lg font-semibold">Internal notes</h2>
                <p class="text-xs">Private to staff – never shown to the customer.</p>
                @can('enquiries.manage')
                    <form method="POST" action="{{ route('admin.enquiries.notes.store', $enquiry) }}" class="mt-4 space-y-3">
                        @csrf
                        <x-form.textarea name="note" label="Add a note" rows="3" required />
                        <button class="btn-navy btn-sm" type="submit">Add note</button>
                    </form>
                @endcan
                <ul class="mt-6 space-y-4">
                    @forelse ($enquiry->notes as $n)
                        <li class="rounded-lg border border-navy/10 p-4 text-sm"><p class="whitespace-pre-line">{{ $n->note }}</p><p class="mt-2 text-xs">{{ $n->author?->name ?? 'Former staff' }} · {{ $n->created_at->format('j M Y, H:i') }}</p></li>
                    @empty
                        <li class="text-sm">No notes yet.</li>
                    @endforelse
                </ul>
            </section>
        </div>
        @can('enquiries.manage')
            <section class="card h-fit p-6" aria-labelledby="manage-h">
                <h2 id="manage-h" class="text-lg font-semibold">Manage</h2>
                <form method="POST" action="{{ route('admin.enquiries.update', $enquiry) }}" class="mt-4 space-y-4">
                    @csrf @method('PATCH')
                    <x-form.select name="status" label="Status" :options="\App\Enums\EnquiryStatus::options()" :value="$enquiry->status" required />
                    <x-form.select name="assigned_to" label="Assigned to" :options="$staff" :value="$enquiry->assigned_to" placeholder="Unassigned" />
                    <button class="btn-primary w-full" type="submit">Save</button>
                </form>
            </section>
        @endcan
    </div>
</x-layouts.admin>
