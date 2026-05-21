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
            <div class="mx-auto flex max-w-7xl items-start justify-between gap-3 px-4 py-3 sm:items-center sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2 sm:gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-olive-700 text-sm font-semibold text-white sm:h-11 sm:w-11">
                        @if (! empty($brandSettings['logo_url']))
                            <img src="{{ $brandSettings['logo_url'] }}" alt="{{ $brandSettings['company_name'] }}" class="h-full w-full object-cover">
                        @else
                            {{ $brandSettings['logo_text'] }}
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-xs font-semibold tracking-[0.18em] text-stone-500 sm:text-sm">{{ $brandSettings['company_name'] }}</p>
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
                <div class="flex flex-1 flex-wrap items-center justify-end gap-2 sm:flex-none sm:gap-3">
                    <details class="group relative lg:hidden">
                        <summary aria-label="{{ __('site.mobile_menu') }}" class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-full border border-stone-300 bg-stone-50 text-stone-700 transition hover:border-olive-600 hover:text-olive-700 [&::-webkit-details-marker]:hidden">
                            <span class="grid gap-1.5">
                                <span class="block h-0.5 w-4 rounded-full bg-current transition group-open:translate-y-2 group-open:rotate-45"></span>
                                <span class="block h-0.5 w-4 rounded-full bg-current transition group-open:opacity-0"></span>
                                <span class="block h-0.5 w-4 rounded-full bg-current transition group-open:-translate-y-2 group-open:-rotate-45"></span>
                            </span>
                        </summary>
                        <div class="fixed left-4 right-4 top-28 z-50 rounded-[1.5rem] border border-stone-200 bg-white p-3 text-sm font-semibold text-stone-700 shadow-2xl shadow-stone-900/10 sm:left-auto sm:right-6 sm:w-80">
                            <a href="{{ route('home') }}#services" class="block rounded-2xl px-4 py-3 transition hover:bg-olive-50 hover:text-olive-800">{{ __('site.nav_services') }}</a>
                            <a href="{{ route('home') }}#process" class="block rounded-2xl px-4 py-3 transition hover:bg-olive-50 hover:text-olive-800">{{ __('site.nav_process') }}</a>
                            <a href="{{ route('home') }}#projects" class="block rounded-2xl px-4 py-3 transition hover:bg-olive-50 hover:text-olive-800">{{ __('site.nav_projects') }}</a>
                            <a href="{{ route('home') }}#team" class="block rounded-2xl px-4 py-3 transition hover:bg-olive-50 hover:text-olive-800">{{ __('site.nav_team') }}</a>
                            <a href="{{ route('blog.index') }}" class="block rounded-2xl px-4 py-3 transition hover:bg-olive-50 hover:text-olive-800">{{ __('site.nav_blog') }}</a>
                            <a href="{{ route('tracking.index') }}" class="block rounded-2xl px-4 py-3 transition hover:bg-olive-50 hover:text-olive-800">{{ __('site.nav_tracking') }}</a>
                        </div>
                    </details>
                    <form method="POST" action="{{ route('locale.switch', app()->getLocale() === 'es' ? 'en' : 'es') }}">
                        @csrf
                        <button type="submit" class="rounded-full border border-stone-300 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.16em] text-stone-700 transition hover:border-olive-600 hover:text-olive-700 sm:tracking-[0.18em]">
                            {{ app()->getLocale() === 'es' ? 'EN' : 'ES' }}
                        </button>
                    </form>
                    <a href="{{ $accessUrl }}" class="rounded-full border border-stone-300 px-3 py-2 text-sm font-semibold leading-none text-stone-700 transition hover:border-olive-600 hover:text-olive-700 sm:px-4">
                        {{ $accessLabel }}
                    </a>
                    <a href="{{ route('home') }}#request" class="basis-full rounded-full bg-olive-700 px-4 py-2.5 text-center text-sm font-semibold leading-tight text-white transition hover:bg-olive-800 sm:basis-auto sm:py-2">
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
