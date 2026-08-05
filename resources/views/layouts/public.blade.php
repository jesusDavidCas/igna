<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php
        $accessUrl = auth()->check()
            ? (auth()->user()->canAccessAdmin() ? route('admin.dashboard') : route('client.dashboard'))
            : route('login');
        $accessLabel = auth()->check() ? __('site.nav_workspace') : __('site.nav_login');
    @endphp
    <head>
        @php
            $seo = $seo ?? app(\App\Support\Seo\SeoManager::class)->meta([
                'title' => $title ?? null,
                'description' => $metaDescription ?? null,
                'robots' => $robots ?? null,
            ]);
            $isNoindex = str_contains($seo['robots'], 'noindex');
        @endphp
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $seo['title'] }}</title>
        <meta name="description" content="{{ $seo['description'] }}">
        <meta name="robots" content="{{ $seo['robots'] }}">
        @unless ($isNoindex)
            <link rel="canonical" href="{{ $seo['canonical'] }}">
        @endunless
        <meta property="og:site_name" content="{{ $seo['site_name'] }}">
        <meta property="og:title" content="{{ $seo['title'] }}">
        <meta property="og:description" content="{{ $seo['description'] }}">
        @unless ($isNoindex)
            <meta property="og:url" content="{{ $seo['canonical'] }}">
        @endunless
        <meta property="og:type" content="{{ $seo['type'] }}">
        <meta property="og:image" content="{{ $seo['image'] }}">
        <meta property="og:locale" content="{{ $seo['locale'] }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seo['title'] }}">
        <meta name="twitter:description" content="{{ $seo['description'] }}">
        <meta name="twitter:image" content="{{ $seo['image'] }}">
        @include('components.favicon-links')
        @foreach ($seo['schema'] as $schema)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
        @endforeach
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="public-site-body text-stone-900">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-full focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-olive-800 focus:shadow-lg">
            {{ __('site.skip_to_content') }}
        </a>
        <header @class(['public-site-header sticky top-0 z-40', 'public-site-header--hero' => request()->routeIs('home')]) @if (request()->routeIs('home')) data-hero-header @endif>
            <div class="site-shell public-header-shell flex items-center justify-between gap-3">
                <a href="{{ route('home') }}" class="public-wordmark group min-w-0" aria-label="{{ $brandSettings['company_name'] }}">
                    <span class="public-wordmark-igna">IGNA</span><span class="public-wordmark-studio">Studio</span>
                </a>
                <nav class="public-nav hidden items-center text-stone-700 xl:flex" aria-label="{{ __('site.footer_navigation') }}">
                    <a href="{{ route('home') }}#services" class="nav-link px-3 py-2.5 transition hover:text-olive-800">{{ __('site.nav_services') }}</a>
                    <a href="{{ route('home') }}#process" class="nav-link px-3 py-2.5 transition hover:text-olive-800">{{ __('site.nav_process') }}</a>
                    <a href="{{ route('home') }}#projects" class="nav-link px-3 py-2.5 transition hover:text-olive-800">{{ __('site.nav_projects') }}</a>
                    <a href="{{ route('home') }}#team" class="nav-link px-3 py-2.5 transition hover:text-olive-800">{{ __('site.nav_team') }}</a>
                    <a href="{{ route('blog.index') }}" class="nav-link px-3 py-2.5 transition hover:text-olive-800">{{ __('site.nav_blog') }}</a>
                    <a href="{{ route('tracking.index') }}" class="nav-link px-3 py-2.5 transition hover:text-olive-800">{{ __('site.nav_tracking') }}</a>
                </nav>
                <div class="public-header-actions flex flex-1 flex-wrap items-center justify-end gap-2 sm:flex-none">
                    <details class="mobile-nav-details group relative xl:hidden">
                        <summary aria-label="{{ __('site.mobile_menu') }}" class="flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-full border border-stone-300 bg-igna-paper text-stone-700 transition hover:border-olive-600 hover:text-olive-700 [&::-webkit-details-marker]:hidden">
                            <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2">
                                <path d="M5 7h14"></path>
                                <path d="M5 12h14"></path>
                                <path d="M5 17h14"></path>
                            </svg>
                        </summary>
                        <div class="public-mobile-menu fixed left-4 right-4 top-24 z-50 rounded-[1.5rem] border border-stone-200 p-3 text-sm font-semibold text-stone-700 shadow-2xl shadow-stone-900/10 sm:left-auto sm:right-6 sm:w-80">
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
                        <button type="submit" class="public-utility-link public-locale-control" aria-label="{{ __('site.language_switch') }}">
                            {{ app()->getLocale() === 'es' ? 'EN' : 'ES' }}
                        </button>
                    </form>
                    <a href="{{ $accessUrl }}" class="public-utility-link public-login-link">
                        {{ $accessLabel }}
                    </a>
                    <a href="{{ route('home') }}#request" class="public-request-link basis-full text-center sm:basis-auto">
                        {{ __('site.cta_request') }}
                    </a>
                </div>
            </div>
        </header>

        @include('partials.flash')

        <main id="main-content">
            {{ $slot ?? '' }}
            @yield('content')
        </main>

        <footer class="footer-route bg-igna-paper/90">
            <div class="site-shell grid gap-10 py-12 lg:grid-cols-[1.2fr_0.8fr_0.8fr]">
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
