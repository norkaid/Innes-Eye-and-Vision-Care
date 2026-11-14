<x-layouts.admin title="Database">
    <x-alert type="info" class="mb-6">
        Read-only view of the website's database ({{ $driver }} · {{ $database }}). Nothing can be changed here; use the other admin pages to edit content.
        Password hashes, login tokens and session data are never shown. Rows may contain personal data: look only at what your work needs.
    </x-alert>
    <div class="card overflow-x-auto">
        <table class="table-admin">
            <thead><tr><th scope="col">Table</th><th scope="col">Database name</th><th scope="col" class="text-right">Rows</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody class="divide-y divide-navy/10 bg-white">
                @foreach ($tables as $t)
                    <tr>
                        <td class="font-semibold text-navy">{{ $t['label'] }}</td>
                        <td><code class="text-xs">{{ $t['name'] }}</code></td>
                        <td class="text-right tabular-nums">{{ number_format($t['rows']) }}</td>
                        <td class="text-right"><a href="{{ route('admin.database.show', $t['name']) }}" class="btn-secondary btn-sm">Browse</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
