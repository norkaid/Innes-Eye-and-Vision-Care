@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="mt-10 flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm">Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</p>
        <ul class="flex flex-wrap items-center gap-1">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="inline-flex h-10 items-center gap-1 rounded-md px-3 text-sm text-slate/50" aria-disabled="true"><x-icon name="chevron-left" class="h-4 w-4" /> Previous</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-10 items-center gap-1 rounded-md border border-navy/15 px-3 text-sm font-semibold no-underline hover:bg-sky"><x-icon name="chevron-left" class="h-4 w-4" /> Previous</a>
                @endif
            </li>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="px-2">…</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex h-10 min-w-10 items-center justify-center rounded-md bg-navy px-3 text-sm font-semibold text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="inline-flex h-10 min-w-10 items-center justify-center rounded-md border border-navy/15 px-3 text-sm font-semibold no-underline hover:bg-sky" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-10 items-center gap-1 rounded-md border border-navy/15 px-3 text-sm font-semibold no-underline hover:bg-sky">Next <x-icon name="chevron-right" class="h-4 w-4" /></a>
                @else
                    <span class="inline-flex h-10 items-center gap-1 rounded-md px-3 text-sm text-slate/50" aria-disabled="true">Next <x-icon name="chevron-right" class="h-4 w-4" /></span>
                @endif
            </li>
        </ul>
    </nav>
@endif
