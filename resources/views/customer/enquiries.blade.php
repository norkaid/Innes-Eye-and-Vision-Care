<x-layouts.account title="My enquiries">
    @forelse ($enquiries as $e)
        <article class="card p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold">{{ $e->subject }}</h2>
                    <p class="text-sm">Sent {{ $e->created_at->format('j M Y, H:i') }}</p>
                </div>
                <x-status-badge :status="$e->status" />
            </div>
            <p class="mt-3 whitespace-pre-line text-sm">{{ $e->message }}</p>
        </article>
    @empty
        <x-empty-state title="No enquiries yet" icon="inbox">Enquiries you send while signed in will appear here. <a href="{{ route('contact') }}">Contact us</a>.</x-empty-state>
    @endforelse
    {{ $enquiries->links() }}
</x-layouts.account>
