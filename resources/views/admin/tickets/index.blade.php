@extends('layouts.panel', ['title' => __('site.admin_tickets'), 'heading' => __('site.admin_tickets')])

@section('content')
    @php
        $createdDirection = $direction === 'desc' ? 'asc' : 'desc';
        $sortLabel = $direction === 'desc' ? __('site.sort_oldest_first') : __('site.sort_newest_first');
        $sortUrl = route('admin.tickets.index', ['sort' => 'created_at', 'direction' => $createdDirection, 'per_page' => $perPage]);
    @endphp

    <div class="mb-5 flex flex-col gap-4 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm md:flex-row md:items-end md:justify-between">
        <form method="GET" action="{{ route('admin.tickets.index') }}" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
            <label for="tickets-per-page" class="grid gap-1 text-sm font-semibold text-stone-700">
                {{ __('site.bulk_delete_per_page') }}
                <select id="tickets-per-page" name="per_page" class="form-input min-w-24" onchange="this.form.submit()">
                    @foreach ([15, 30, 50] as $size)
                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </label>
            <a href="{{ $sortUrl }}" class="inline-flex min-h-11 items-center gap-1.5 rounded-full border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-50" aria-label="{{ $sortLabel }}" title="{{ $sortLabel }}">
                {{ __('site.created_at') }} <span aria-hidden="true" class="text-base leading-none">{{ $direction === 'desc' ? '↓' : '↑' }}</span>
            </a>
        </form>

        @if ($selectionContext)
            <form id="bulk-ticket-delete-form" method="POST" action="{{ route('admin.tickets.bulk-delete.preview') }}" class="flex flex-wrap items-center gap-3" data-bulk-delete-form>
                @csrf
                <input type="hidden" name="selection_context" value="{{ $selectionContext }}">
                <span class="text-sm font-semibold text-stone-700" aria-live="polite" data-bulk-delete-count>{{ __('site.bulk_delete_selected_count', ['count' => 0]) }}</span>
                <button type="button" class="min-h-11 rounded-full border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-stone-700 transition hover:bg-stone-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-olive-700" data-bulk-delete-toggle-all>{{ __('site.bulk_delete_select_visible') }}</button>
                <button type="submit" class="min-h-11 rounded-full bg-rose-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-700 disabled:cursor-not-allowed disabled:bg-stone-400" data-bulk-delete-submit disabled>{{ __('site.bulk_delete_review') }}</button>
            </form>
        @endif
    </div>

    <div class="space-y-4 md:hidden">
        @forelse ($tickets as $ticket)
            @php($isBulkProtected = $bulkProtectedTicketIds->has($ticket->id))
            <article class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <a class="inline-flex max-w-full items-center justify-center truncate rounded-full bg-olive-50 px-4 py-2 text-sm font-semibold text-olive-800 ring-1 ring-olive-200 transition hover:bg-olive-100" href="{{ route('admin.tickets.show', $ticket) }}" title="{{ $ticket->ticket_code }}">{{ $ticket->ticket_code }}</a>
                    @if ($selectionContext)<input type="checkbox" class="h-5 w-5 rounded border-stone-400 text-rose-700 focus:ring-rose-700" value="{{ $ticket->id }}" data-bulk-delete-ticket @disabled($isBulkProtected) aria-label="{{ __('site.bulk_delete_select_ticket', ['ticket' => $ticket->ticket_code]) }}">@endif
                </div>
                <h2 class="mt-3 break-words font-semibold text-stone-950">{{ $ticket->localizedProjectName() }}</h2>
                @if ($isBulkProtected)<p class="mt-2 text-sm font-medium text-amber-800">{{ __('site.bulk_delete_individual_review_badge') }}</p>@endif
                <dl class="mt-4 grid gap-3 text-sm">
                    <div class="flex items-center justify-between gap-3"><dt class="text-stone-500">{{ __('site.created_at') }}</dt><dd class="font-semibold text-stone-900">{{ $ticket->created_at?->translatedFormat('M j, Y') }}</dd></div>
                    <div class="flex items-center justify-between gap-3"><dt class="text-stone-500">{{ __('site.form_service') }}</dt><dd class="font-semibold text-stone-900">{{ $ticket->serviceDisplayName() }}</dd></div>
                    <div class="flex items-center justify-between gap-3"><dt class="text-stone-500">{{ __('site.current_stage') }}</dt><dd class="font-semibold text-stone-900">{{ $ticket->currentStage?->localizedName() ?? __('site.pending_assignment') }}</dd></div>
                </dl>
            </article>
        @empty
            <p class="rounded-2xl border border-stone-200 bg-white p-5 text-center text-stone-500">{{ __('site.no_projects_yet') }}</p>
        @endforelse
    </div>

    <div class="hidden rounded-2xl border border-stone-200 bg-white p-6 shadow-sm md:block"><div class="overflow-x-auto">
        <table class="min-w-[1240px] table-fixed text-left text-sm"><thead class="text-stone-500"><tr>
            @if ($selectionContext)<th class="w-14 pb-3"><span class="sr-only">{{ __('site.bulk_delete_selection') }}</span></th>@endif
            <th class="w-52 pb-3">{{ __('site.form_ticket_code') }}</th><th class="w-80 pb-3">{{ __('site.form_project_name') }}</th><th class="w-64 pb-3">{{ __('site.form_email') }}</th><th class="w-64 pb-3">{{ __('site.form_service') }}</th><th class="w-44 pb-3">{{ __('site.created_at') }}</th><th class="w-52 pb-3">{{ __('site.current_stage') }}</th>
        </tr></thead><tbody class="divide-y divide-stone-100">
            @forelse ($tickets as $ticket)
                @php($isBulkProtected = $bulkProtectedTicketIds->has($ticket->id))
                <tr>
                    @if ($selectionContext)<td class="py-4 pr-3"><input type="checkbox" class="h-5 w-5 rounded border-stone-400 text-rose-700 focus:ring-rose-700" value="{{ $ticket->id }}" data-bulk-delete-ticket @disabled($isBulkProtected) aria-label="{{ __('site.bulk_delete_select_ticket', ['ticket' => $ticket->ticket_code]) }}"></td>@endif
                    <td class="py-4 pr-5"><a class="inline-flex max-w-full items-center justify-center truncate rounded-full bg-olive-50 px-4 py-2 text-sm font-semibold text-olive-800 ring-1 ring-olive-200 transition hover:bg-olive-100" href="{{ route('admin.tickets.show', $ticket) }}" title="{{ $ticket->ticket_code }}">{{ $ticket->ticket_code }}</a></td>
                    <td class="truncate py-4 pr-5" title="{{ $ticket->localizedProjectName() }}">{{ $ticket->localizedProjectName() }} @if ($isBulkProtected)<span class="ml-2 text-xs font-semibold text-amber-800">{{ __('site.bulk_delete_individual_review_badge') }}</span>@endif</td>
                    <td class="truncate py-4 pr-5" title="{{ $ticket->email }}">{{ $ticket->email }}</td><td class="truncate py-4 pr-5" title="{{ $ticket->serviceDisplayName() }}">{{ $ticket->serviceDisplayName() }}</td><td class="py-4 pr-5">{{ $ticket->created_at?->translatedFormat('M j, Y') }}</td><td class="py-4 pr-5">{{ $ticket->currentStage?->localizedName() ?? __('site.pending_assignment') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-8 text-center text-stone-500">{{ __('site.no_projects_yet') }}</td></tr>
            @endforelse
        </tbody></table>
    </div></div>

    <div class="mt-8">{{ $tickets->links() }}</div>

    @if ($selectionContext)
        <script>
            (() => {
                const form = document.querySelector('[data-bulk-delete-form]');
                if (!form) return;
                const selected = new Set();
                const checkboxes = [...document.querySelectorAll('[data-bulk-delete-ticket]')];
                const count = form.querySelector('[data-bulk-delete-count]');
                const submit = form.querySelector('[data-bulk-delete-submit]');
                const toggle = form.querySelector('[data-bulk-delete-toggle-all]');
                const countLabel = @json(__('site.bulk_delete_selected_count', ['count' => ':count']));
                const render = () => {
                    checkboxes.forEach((checkbox) => { checkbox.checked = selected.has(checkbox.value); });
                    count.textContent = countLabel.replace(':count', selected.size);
                    submit.disabled = selected.size === 0;
                    toggle.textContent = selected.size === checkboxes.filter((checkbox) => !checkbox.disabled).length ? @json(__('site.bulk_delete_deselect_visible')) : @json(__('site.bulk_delete_select_visible'));
                };
                checkboxes.forEach((checkbox) => checkbox.addEventListener('change', () => { checkbox.checked ? selected.add(checkbox.value) : selected.delete(checkbox.value); render(); }));
                toggle.addEventListener('click', () => { const selectable = checkboxes.filter((checkbox) => !checkbox.disabled); const allSelected = selectable.length > 0 && selectable.every((checkbox) => selected.has(checkbox.value)); selectable.forEach((checkbox) => allSelected ? selected.delete(checkbox.value) : selected.add(checkbox.value)); render(); });
                form.addEventListener('submit', () => { form.querySelectorAll('[data-bulk-delete-id]').forEach((input) => input.remove()); selected.forEach((id) => { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'ticket_ids[]'; input.value = id; input.dataset.bulkDeleteId = ''; form.appendChild(input); }); });
                render();
            })();
        </script>
    @endif
@endsection
