<x-layouts.admin :title="$label">
    <x-slot:breadcrumb><a href="{{ route('admin.database.index') }}">Database</a></x-slot:breadcrumb>
    <p class="mb-4 text-sm">
        Table <code>{{ $table }}</code> · {{ number_format($rows->total()) }} {{ Str::plural('row', $rows->total()) }} · newest first · read-only.
        @if ($hidden) Hidden for security: {{ implode(', ', $hidden) }}. @endif
    </p>
    @if ($rows->isEmpty())
        <x-empty-state title="This table is empty" icon="list" />
    @else
        <div class="card overflow-x-auto">
            <table class="table-admin text-xs">
                <thead><tr>@foreach ($columns as $col)<th scope="col" class="whitespace-nowrap">{{ $col }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-navy/10 bg-white">
                    @foreach ($rows as $row)
                        <tr>
                            @foreach ($columns as $col)
                                @php($value = $row->{$col})
                                <td class="max-w-xs align-top">
                                    @if (is_null($value))
                                        <span class="text-slate/60">NULL</span>
                                    @else
                                        <span class="block truncate" title="{{ Str::limit((string) $value, 500) }}">{{ Str::limit((string) $value, 80) }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $rows->links() }}</div>
    @endif
</x-layouts.admin>
