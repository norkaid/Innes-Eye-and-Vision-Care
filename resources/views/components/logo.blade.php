@props(['light' => false])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg class="h-9 w-9 shrink-0" viewBox="0 0 64 64" aria-hidden="true"><rect width="64" height="64" rx="14" fill="{{ $light ? '#ffffff' : '#123047' }}"/><path d="M8 32C18 16 46 16 56 32C46 48 18 48 8 32Z" fill="{{ $light ? '#123047' : '#ffffff' }}"/><circle cx="32" cy="32" r="10" fill="#168C8C"/><circle cx="32" cy="32" r="4" fill="{{ $light ? '#ffffff' : '#123047' }}"/></svg>
    <span class="leading-tight">
        <span class="block font-display text-lg font-bold {{ $light ? 'text-white' : 'text-navy' }}">Innes Eye</span>
        <span class="block text-xs font-semibold uppercase tracking-wider {{ $light ? 'text-sky' : 'text-teal-700' }}">&amp; Visioncare Centre</span>
    </span>
</span>
