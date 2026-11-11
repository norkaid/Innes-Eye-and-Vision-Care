@props(['items'])
<ol class="flex flex-wrap items-center gap-1 text-slate">
    <li><a href="{{ route('home') }}" class="text-teal-700">Home</a></li>
    @foreach ($items as $label => $url)
        <li aria-hidden="true">/</li>
        <li>@if ($url)<a href="{{ $url }}" class="text-teal-700">{{ $label }}</a>@else<span aria-current="page">{{ $label }}</span>@endif</li>
    @endforeach
</ol>
