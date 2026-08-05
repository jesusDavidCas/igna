@php
    $faviconSettings = $brandSettings ?? app(\App\Support\Settings\BrandSettings::class)->publicPayload();
    $faviconUrl = $faviconSettings['favicon_url'] ?? route('brand.favicon');
@endphp
<link rel="icon" href="{{ $faviconUrl }}">
<link rel="shortcut icon" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
