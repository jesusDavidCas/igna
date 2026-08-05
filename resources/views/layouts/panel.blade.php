<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ $title ?? 'IGNA Studio' }}</title>
        @include('components.favicon-links')
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
                        <a href="{{ route('admin.proposals.index') }}" class="panel-link {{ request()->routeIs('admin.proposals.*') ? 'panel-link-active' : '' }}">{{ __('site.admin_proposals') }}</a>
                        <a href="{{ route('admin.proposal-templates.index') }}" class="panel-link {{ request()->routeIs('admin.proposal-templates.*') ? 'panel-link-active' : '' }}">{{ __('site.admin_proposal_templates') }}</a>
                        <a href="{{ route('admin.team.index') }}" class="panel-link {{ request()->routeIs('admin.team.*') ? 'panel-link-active' : '' }}">{{ __('site.admin_team') }}</a>
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

            <div class="min-w-0">
                <header class="border-b border-stone-200 bg-white">
                    <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-5 sm:px-6 md:flex-row md:items-center md:justify-between">
                        <div class="min-w-0">
                            <p class="text-sm text-stone-500">{{ __('site.panel_welcome') }}</p>
                            <h1 class="break-words text-xl font-semibold text-stone-900">{{ $heading ?? 'IGNA Studio' }}</h1>
                        </div>
                        <div class="flex min-w-0 flex-wrap items-center gap-3 md:justify-end">
                            <form method="POST" action="{{ route('locale.switch', app()->getLocale() === 'es' ? 'en' : 'es') }}">
                                @csrf
                                <button type="submit" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-olive-700 hover:text-olive-800" aria-label="{{ __('site.language_switch') }}">
                                    {{ app()->getLocale() === 'es' ? 'EN' : 'ES' }}
                                </button>
                            </form>
                            <div class="min-w-0 text-left sm:text-right">
                                <p class="text-sm font-semibold text-stone-900">{{ auth()->user()->name }}</p>
                                <p class="break-all text-xs text-stone-500">{{ auth()->user()->email }}</p>
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

                <main class="mx-auto max-w-7xl px-6 py-8">
                    @yield('content')
                </main>
            </div>
        </div>

        <div id="confirm-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-stone-950/50 px-4">
            <div class="w-full max-w-md rounded-[2rem] bg-white p-6 shadow-2xl">
                <h2 id="confirm-modal-title" class="text-lg font-semibold text-stone-950"></h2>
                <p id="confirm-modal-message" class="mt-3 text-sm leading-6 text-stone-600"></p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" id="confirm-modal-cancel" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700">{{ __('site.cancel') }}</button>
                    <button type="button" id="confirm-modal-confirm" class="rounded-full bg-olive-700 px-4 py-2 text-sm font-semibold text-white">{{ __('site.confirm_change') }}</button>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.getElementById('confirm-modal');
                const title = document.getElementById('confirm-modal-title');
                const message = document.getElementById('confirm-modal-message');
                const cancel = document.getElementById('confirm-modal-cancel');
                const confirm = document.getElementById('confirm-modal-confirm');
                let pendingForm = null;

                document.querySelectorAll('form[data-confirm-title]').forEach((form) => {
                    form.addEventListener('submit', (event) => {
                        if (form.dataset.confirmed === 'true') return;
                        event.preventDefault();
                        pendingForm = form;
                        title.textContent = form.dataset.confirmTitle;
                        message.textContent = form.dataset.confirmMessage;
                        confirm.className = form.dataset.confirmDanger === 'true'
                            ? 'rounded-full bg-rose-700 px-4 py-2 text-sm font-semibold text-white'
                            : 'rounded-full bg-olive-700 px-4 py-2 text-sm font-semibold text-white';
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                    });
                });

                cancel.addEventListener('click', () => {
                    pendingForm = null;
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                });

                confirm.addEventListener('click', () => {
                    if (!pendingForm) return;
                    pendingForm.dataset.confirmed = 'true';
                    pendingForm.submit();
                });

                document.querySelectorAll('[data-delete-modal-trigger]').forEach((trigger) => {
                    const modal = document.getElementById(trigger.dataset.deleteModalTrigger);
                    if (!modal || trigger.disabled) return;

                    const cancelButton = modal.querySelector('[data-delete-modal-cancel]');
                    const submitButton = modal.querySelector('[data-delete-modal-submit]');
                    const form = modal.querySelector('[data-delete-modal-form]');
                    const focusableSelector = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
                    let returnFocus = null;
                    let submitting = false;

                    const focusableElements = () => Array.from(modal.querySelectorAll(focusableSelector))
                        .filter((element) => !element.disabled && element.offsetParent !== null);

                    const closeModal = () => {
                        if (submitting) return;
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                        document.body.classList.remove('overflow-hidden');
                        returnFocus?.focus();
                    };

                    trigger.addEventListener('click', () => {
                        returnFocus = trigger;
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                        document.body.classList.add('overflow-hidden');
                        cancelButton?.focus();
                    });

                    cancelButton?.addEventListener('click', closeModal);

                    modal.addEventListener('click', (event) => {
                        if (event.target === modal) {
                            closeModal();
                        }
                    });

                    modal.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape') {
                            event.preventDefault();
                            closeModal();
                            return;
                        }

                        if (event.key !== 'Tab') return;

                        const elements = focusableElements();
                        const first = elements[0];
                        const last = elements[elements.length - 1];

                        if (!first || !last) return;

                        if (event.shiftKey && document.activeElement === first) {
                            event.preventDefault();
                            last.focus();
                        } else if (!event.shiftKey && document.activeElement === last) {
                            event.preventDefault();
                            first.focus();
                        }
                    });

                    form?.addEventListener('submit', (event) => {
                        if (submitting) {
                            event.preventDefault();
                            return;
                        }

                        submitting = true;
                        submitButton.disabled = true;
                        submitButton.textContent = '{{ __('site.deletion_submitting') }}';
                    });
                });
            });
        </script>
    </body>
</html>
