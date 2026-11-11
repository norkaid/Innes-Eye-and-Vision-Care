@props(['status', 'size' => 'md'])
@php
    $tones = [
        'amber' => 'bg-amber-50 text-amber-ink ring-amber-300',
        'teal' => 'bg-teal-50 text-teal-700 ring-teal/40',
        'navy' => 'bg-navy-50 text-navy ring-navy/30',
        'gray' => 'bg-gray-100 text-gray-700 ring-gray-300',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full font-semibold ring-1 ring-inset '.($tones[$status->tone()] ?? $tones['gray']).' '.($size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-xs')]) }}>
    <x-icon :name="$status->icon()" class="h-3.5 w-3.5" />
    {{ $status->label() }}
</span>
