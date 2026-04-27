<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php
        $accessUrl = auth()->check()
            ? (auth()->user()->canAccessAdmin() ? route('admin.dashboard') : route('client.dashboard'))
            : route('login');
        $accessLabel = auth()->check() ? __('site.nav_workspace') : __('site.nav_login');
    @endphp
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'IGNA Studio' }}</title>
        <meta name="description" content="{{ $metaDescription ?? __('site.meta_description') }}">
        @if (! empty($brandSettings['favicon_url']))
            <link rel="icon" href="{{ $brandSettings['favicon_url'] }}">
        @endif
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-stone-50 text-stone-900">
        <header class="sticky top-0 z-40 border-b border-stone-200/80 bg-stone-50/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full bg-olive-700 text-sm font-semibold text-white">
                        @if (! empty($brandSettings['logo_url']))
                            <img src="{{ $brandSettings['logo_url'] }}" alt="{{ $brandSettings['company_name'] }}" class="h-full w-full object-cover">
                        @else
                            {{ $brandSettings['logo_text'] }}
                        @endif
                    </div>
                    <div>
                        <p class="text-sm font-semibold tracking-[0.18em] text-stone-500">{{ $brandSettings['company_name'] }}</p>
                        <p class="hidden text-sm text-stone-600 md:block">{{ __('site.brand_tagline') }}</p>
                    </div>
                </a>
                <nav class="hidden items-center gap-6 text-sm font-medium text-stone-700 lg:flex">
                    <a href="{{ route('home') }}#services" class="transition hover:text-olive-700">{{ __('site.nav_services') }}</a>
                    <a href="{{ route('home') }}#process" class="transition hover:text-olive-700">{{ __('site.nav_process') }}</a>
                    <a href="{{ route('home') }}#projects" class="transition hover:text-olive-700">{{ __('site.nav_projects') }}</a>
                    <a href="{{ route('home') }}#team" class="transition hover:text-olive-700">{{ __('site.nav_team') }}</a>
                    <a href="{{ route('blog.index') }}" class="transition hover:text-olive-700">{{ __('site.nav_blog') }}</a>
                    <a href="{{ route('tracking.index') }}" class="transition hover:text-olive-700">{{ __('site.nav_tracking') }}</a>
                </nav>
                <div class="flex items-center gap-3">
                    <form method="POST" action="{{ route('locale.switch', app()->getLocale() === 'es' ? 'en' : 'es') }}">
                        @csrf
                        <button type="submit" class="rounded-full border border-stone-300 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-stone-700 transition hover:border-olive-600 hover:text-olive-700">
                            {{ app()->getLocale() === 'es' ? 'EN' : 'ES' }}
                        </button>
                    </form>
                    <a href="{{ $accessUrl }}" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-olive-600 hover:text-olive-700">
                        {{ $accessLabel }}
                    </a>
                    <a href="{{ route('home') }}#request" class="rounded-full bg-olive-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-olive-800">
                        {{ __('site.cta_request') }}
                    </a>
                </div>
            </div>
        </header>

        @include('partials.flash')

        <main>
            {{ $slot ?? '' }}
            @yield('content')
        </main>

        <footer class="border-t border-stone-200 bg-stone-100/80">
            <div class="mx-auto grid max-w-7xl gap-10 px-6 py-12 lg:grid-cols-[1.2fr_0.8fr_0.8fr] lg:px-8">
                <div class="space-y-4">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-olive-700">{{ $brandSettings['company_name'] }}</p>
                    <p class="max-w-xl text-sm leading-7 text-stone-600">{{ __('site.footer_description') }}</p>
                </div>
                <div class="space-y-3 text-sm text-stone-600">
                    <p class="font-semibold text-stone-900">{{ __('site.footer_navigation') }}</p>
                    <a href="{{ route('home') }}#services" class="block hover:text-olive-700">{{ __('site.nav_services') }}</a>
                    <a href="{{ route('tracking.index') }}" class="block hover:text-olive-700">{{ __('site.nav_tracking') }}</a>
                    <a href="{{ route('blog.index') }}" class="block hover:text-olive-700">{{ __('site.nav_blog') }}</a>
                </div>
                <div class="space-y-3 text-sm text-stone-600">
                    <p class="font-semibold text-stone-900">{{ __('site.footer_access') }}</p>
                    <a href="{{ $accessUrl }}" class="block hover:text-olive-700">{{ $accessLabel }}</a>
                    <p>{{ __('site.footer_location') }}</p>
                </div>
            </div>
        </footer>
    </body>
</html>
