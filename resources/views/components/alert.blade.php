@props(['type' => 'info', 'title' => null])
@php
    $styles = [
        'success' => ['bg-teal-50 border-teal text-navy', 'check', 'Success'],
        'error' => ['bg-red-50 border-red-700 text-red-900', 'alert', 'Error'],
        'warning' => ['bg-amber-50 border-amber-500 text-amber-ink', 'alert', 'Important'],
        'info' => ['bg-sky border-navy/30 text-navy', 'info', 'Note'],
    ][$type];
@endphp
<div {{ $attributes->merge(['class' => 'flex gap-3 rounded-lg border-l-4 p-4 '.$styles[0]]) }} role="{{ in_array($type, ['error', 'warning']) ? 'alert' : 'status' }}">
    <x-icon :name="$styles[1]" class="mt-0.5 h-5 w-5 shrink-0" />
    <div class="text-sm leading-6">
        <p class="font-semibold">{{ $title ?? $styles[2] }}</p>
        <div>{{ $slot }}</div>
    </div>
</div>
