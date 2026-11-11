<x-layouts.admin title="Activity log">
    <p class="mb-4 text-sm">Administrative changes recorded by the system. Passwords, notes and message contents are never logged.</p>
    @if ($logs->isEmpty())
        <x-empty-state title="No activity yet" icon="activity" />
    @else
        <div class="card overflow-x-auto">
            <table class="table-admin">
                <thead><tr><th scope="col">When</th><th scope="col">Who</th><th scope="col">Action</th><th scope="col">Record</th><th scope="col">Details</th></tr></thead>
                <tbody class="divide-y divide-navy/10 bg-white">
                    @foreach ($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap">{{ $log->created_at->format('j M Y, H:i') }}</td>
                            <td>{{ $log->user?->name ?? 'System' }}</td>
                            <td class="font-semibold text-navy">{{ $log->action }}</td>
                            <td>{{ $log->entity_type }}{{ $log->entity_id ? ' #'.$log->entity_id : '' }}</td>
                            <td class="text-xs">@if ($log->metadata)<code class="break-all">{{ json_encode($log->metadata) }}</code>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $logs->links() }}
    @endif
</x-layouts.admin>
