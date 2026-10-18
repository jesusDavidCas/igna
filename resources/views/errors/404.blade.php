<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ __('site.not_found_title') }} | IGNA Studio</title>
        @include('components.favicon-links')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-igna-paper text-stone-900">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-olive-800 focus:shadow-lg">
            {{ __('site.skip_to_content') }}
        </a>

        <header class="border-b border-stone-200 bg-white">
            <div class="site-shell flex items-center justify-between gap-4 py-5">
                <a href="{{ route('home') }}" class="public-wordmark" aria-label="IGNA Studio">
                    <span class="public-wordmark-igna">IGNA</span><span class="public-wordmark-studio">Studio</span>
                </a>
                <a href="{{ route('home') }}#request" class="public-request-link text-center">
                    {{ __('site.cta_request') }}
                </a>
            </div>
        </header>

        <main id="main-content" class="site-shell flex min-h-[calc(100vh-85px)] items-center py-12 sm:py-20">
            <section class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-olive-700">404</p>
                <h1 class="mt-4 text-4xl font-semibold text-stone-950 sm:text-5xl">{{ __('site.not_found_heading') }}</h1>
                <p class="mt-5 max-w-xl text-base leading-8 text-stone-600 sm:text-lg">{{ __('site.not_found_message') }}</p>
                <nav class="mt-8 flex flex-wrap gap-3" aria-label="{{ __('site.footer_navigation') }}">
                    <a href="{{ route('home') }}" class="public-request-link text-center">
                        {{ __('site.not_found_home') }}
                    </a>
                    <a href="{{ route('blog.index') }}" class="inline-flex items-center justify-center border border-stone-300 px-5 py-2.5 text-sm font-semibold text-stone-800 transition hover:border-olive-600 hover:text-olive-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-olive-700">
                        {{ __('site.not_found_blog') }}
                    </a>
                </nav>
            </section>
        </main>
    </body>
</html>
