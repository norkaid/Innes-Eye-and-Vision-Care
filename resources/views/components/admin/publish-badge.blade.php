@props(['published', 'archived' => false])
@if ($archived)
    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 ring-1 ring-gray-300"><x-icon name="x" class="h-3.5 w-3.5" /> Archived</span>
@elseif ($published)
    <span class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700 ring-1 ring-teal/40"><x-icon name="check" class="h-3.5 w-3.5" /> Published</span>
@else
    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-ink ring-1 ring-amber-300"><x-icon name="clock" class="h-3.5 w-3.5" /> Draft</span>
@endif
