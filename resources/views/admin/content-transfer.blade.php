<x-layouts.admin title="Content transfer">
    <p class="mb-6 max-w-3xl text-sm">
        Move the website's content from one copy of the site to another, for example from your PC to the live site.
        <strong>Included:</strong> business details and logo, media library images, branches with opening hours, services, product categories, products, article categories, health articles and team profiles.
        <strong>Never included:</strong> staff and customer accounts, appointments, enquiries and the activity log.
        You can still add and edit everything directly in the admin pages of either site.
    </p>

    @if (session('import_result'))
        <div class="card mb-6 overflow-x-auto">
            <table class="table-admin">
                <caption class="px-4 pt-4 text-left font-semibold text-navy">Last import</caption>
                <thead><tr><th scope="col">Content</th><th scope="col" class="text-right">Added</th><th scope="col" class="text-right">Updated</th></tr></thead>
                <tbody class="divide-y divide-navy/10 bg-white">
                    @foreach (session('import_result') as $label => $counts)
                        <tr><td>{{ $label }}</td><td class="text-right tabular-nums">{{ $counts['created'] }}</td><td class="text-right tabular-nums">{{ $counts['updated'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('admin.content-transfer.export') }}" class="card space-y-5 p-6">
            @csrf
            <h2 class="text-lg font-semibold">1. Export content from this site</h2>
            <p class="text-sm">Downloads one file (<code>innes-content-….json.gz</code>) with everything listed below.</p>
            <table class="w-full text-sm">
                <thead><tr class="text-left"><th scope="col" class="py-1 font-semibold text-navy">Content</th><th scope="col" class="py-1 text-right font-semibold text-navy">Items</th><th scope="col" class="py-1 text-right font-semibold text-navy">Still demo</th></tr></thead>
                <tbody class="divide-y divide-navy/10">
                    @foreach ($summary as $label => $counts)
                        <tr><td class="py-1">{{ $label }}</td><td class="py-1 text-right tabular-nums">{{ $counts['all'] }}</td><td class="py-1 text-right tabular-nums">{{ $counts['demo'] ?: '–' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
            <x-form.checkbox name="include_demo" label="Also include items that are still demo content" hint="Demo items have “(Demo)” in the name or “DEMO DATA” in the text. Leave this unticked so the live site starts without them." />
            <button class="btn-primary" type="submit">Download export file</button>
        </form>

        <form method="POST" action="{{ route('admin.content-transfer.import') }}" enctype="multipart/form-data" class="card space-y-5 p-6" novalidate>
            @csrf
            <h2 class="text-lg font-semibold">2. Import content into this site</h2>
            <p class="text-sm">Choose an export file made on another copy of the site. New items are added. Items with the same web address (e.g. <code>/products/aviator</code>) are <strong>overwritten</strong> with the version in the file. Nothing is deleted.</p>
            <div>
                <label for="f-file" class="form-label">Export file</label>
                <input id="f-file" type="file" name="file" accept=".gz,.json,application/gzip,application/json" required
                    class="block w-full text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-navy file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-navy-700">
                <p class="form-hint">This server accepts files up to {{ ini_get('upload_max_filesize') }}. For bigger files use the command <code>php artisan innes:import-content</code> (see the Hostinger guide).</p>
                @error('file')<p class="form-error"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" /> {{ $message }}</p>@enderror
            </div>
            <x-form.checkbox name="confirm" label="I understand that matching items on this site will be overwritten" />
            <button class="btn-primary" type="submit">Import content</button>
        </form>
    </div>
</x-layouts.admin>
