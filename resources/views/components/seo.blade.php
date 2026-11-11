@props(['title' => null, 'description' => null, 'image' => null, 'noindex' => false, 'canonical' => null])
@php
    $siteName = $site->get('business_name');
    $fullTitle = $title ? $title.' | '.$siteName : $siteName.' – '.$site->get('tagline');
    $description = $description ?: 'Eye examinations, optical frames and eye-care services from '.$siteName.', Ghana. Request an appointment online.';
    $canonical = $canonical ?: url()->current();
    $image = $image ?: asset('images/hero-illustration.svg');
@endphp
<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($description), 160) }}">
<link rel="canonical" href="{{ $canonical }}">
@if ($noindex)
    <meta name="robots" content="noindex, nofollow">
@endif
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($description), 200) }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
<meta name="twitter:card" content="summary_large_image">
