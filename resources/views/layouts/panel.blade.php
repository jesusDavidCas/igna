<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'IGNA Studio' }}</title>
        @if (! empty($brandSettings['favicon_url']))
            <link rel="icon" href="{{ $brandSettings['favicon_url'] }}">
        @endif
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-stone-100 text-stone-900">
        <div class="min-h-screen lg:grid lg:grid-cols-[280px_minmax(0,1fr)]">
            <aside class="border-r border-stone-200 bg-stone-950 px-6 py-8 text-stone-200">
                <a href="{{ auth()->user()->canAccessAdmin() ? route('admin.dashboard') : route('client.dashboard') }}" class="block">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-olive-700 text-sm font-semibold text-white">
                            @if (! empty($brandSettings['logo_url']))
                                <img src="{{ $brandSettings['logo_url'] }}" alt="{{ $brandSettings['company_name'] }}" class="h-full w-full object-cover">
                            @else
                                {{ $brandSettings['logo_text'] }}
                            @endif
                        </div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-olive-300">{{ $brandSettings['company_name'] }}</p>
                    </div>
                    <p class="mt-2 text-sm text-stone-400">{{ auth()->user()->role->label() }}</p>
                </a>

                <nav class="mt-10 space-y-2 text-sm">
                    @if (auth()->user()->canAccessAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="panel-link {{ request()->routeIs('admin.dashboard') ? 'panel-link-active' : '' }}">{{ __('site.admin_dashboard') }}</a>
                        <a href="{{ route('admin.services.index') }}" class="panel-link {{ request()->routeIs('admin.services.*') ? 'panel-link-active' : '' }}">{{ __('site.admin_services') }}</a>
                        <a href="{{ route('admin.tickets.index') }}" class="panel-link {{ request()->routeIs('admin.tickets.*') ? 'panel-link-active' : '' }}">{{ __('site.admin_tickets') }}</a>
                        <a href="{{ route('admin.blog.index') }}" class="panel-link {{ request()->routeIs('admin.blog.*') ? 'panel-link-active' : '' }}">{{ __('site.admin_blog') }}</a>
                        @if (auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.users.index') }}" class="panel-link {{ request()->routeIs('admin.users.*') ? 'panel-link-active' : '' }}">{{ __('site.admin_users') }}</a>
                            <a href="{{ route('admin.settings.edit') }}" class="panel-link {{ request()->routeIs('admin.settings.*') ? 'panel-link-active' : '' }}">{{ __('site.admin_settings') }}</a>
                        @endif
                    @else
                        <a href="{{ route('client.dashboard') }}" class="panel-link {{ request()->routeIs('client.dashboard') ? 'panel-link-active' : '' }}">{{ __('site.client_dashboard') }}</a>
                    @endif
                </nav>
            </aside>

            <div>
                <header class="border-b border-stone-200 bg-white">
                    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
                        <div>
                            <p class="text-sm text-stone-500">{{ __('site.panel_welcome') }}</p>
                            <h1 class="text-xl font-semibold text-stone-900">{{ $heading ?? 'IGNA Studio' }}</h1>
                        </div>
                        <div class="flex items-center gap-3">
                            <form method="POST" action="{{ route('locale.switch', app()->getLocale() === 'es' ? 'en' : 'es') }}">
                                @csrf
                                <button type="submit" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-olive-700 hover:text-olive-800" aria-label="{{ __('site.language_switch') }}">
                                    {{ app()->getLocale() === 'es' ? 'EN' : 'ES' }}
                                </button>
                            </form>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-stone-900">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-stone-500">{{ auth()->user()->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-stone-900 hover:text-stone-900">
                                    {{ __('site.logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </header>

                @include('partials.flash')

                <main class="mx-auto max-w-6xl px-6 py-8">
                    @yield('content')
                </main>
            </div>
        </div>
    </body>
</html>
