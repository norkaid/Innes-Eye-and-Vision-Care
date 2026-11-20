<x-layouts.account title="Notifications">
    @forelse ($notifications as $n)
        <article class="card flex gap-4 p-5 {{ $n->read_at ? '' : 'border-l-4 border-teal' }}">
            <span class="mt-0.5 rounded-full bg-sky p-2 text-teal-700"><x-icon name="bell" class="h-4 w-4" /></span>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="font-semibold">{{ $n->data['title'] ?? 'Update' }}@unless ($n->read_at) <span class="ml-1 rounded-full bg-teal px-2 py-0.5 text-xs text-white">New</span>@endunless</h2>
                    <time class="text-xs" datetime="{{ $n->created_at->toIso8601String() }}">{{ $n->created_at->format('j M Y, H:i') }}</time>
                </div>
                <p class="mt-1 text-sm">{{ $n->data['body'] ?? '' }}</p>
                @if (! empty($n->data['url']))<a href="{{ $n->data['url'] }}" class="mt-2 inline-block text-sm font-semibold">View details</a>@endif
            </div>
        </article>
    @empty
        <x-empty-state title="No notifications yet" icon="bell">Updates about your appointment requests and orders will appear here.</x-empty-state>
    @endforelse
    {{ $notifications->links() }}
</x-layouts.account>
