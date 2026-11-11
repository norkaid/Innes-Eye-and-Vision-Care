@props(['label', 'value', 'icon' => 'info', 'href' => null, 'note' => null])
<div class="card relative p-5">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold">{{ $label }}</p>
            <p class="mt-1 text-3xl font-bold text-navy">{{ number_format($value) }}</p>
            @if ($note)<p class="mt-1 text-xs">{{ $note }}</p>@endif
        </div>
        <span class="rounded-lg bg-sky p-2 text-teal-700"><x-icon :name="$icon" /></span>
    </div>
    @if ($href)<a href="{{ $href }}" class="mt-3 inline-flex text-sm font-semibold after:absolute after:inset-0">View <span class="sr-only">{{ $label }}</span></a>@endif
</div>
