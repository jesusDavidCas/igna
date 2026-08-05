@extends('layouts.panel', ['title' => __('site.admin_tickets'), 'heading' => __('site.admin_tickets')])

@section('content')
    @php
        $createdDirection = $direction === 'desc' ? 'asc' : 'desc';
        $sortLabel = $direction === 'desc' ? __('site.sort_oldest_first') : __('site.sort_newest_first');
        $sortUrl = route('admin.tickets.index', [
            'sort' => 'created_at',
            'direction' => $createdDirection,
        ]);
    @endphp

    <div class="space-y-4 md:hidden">
        <a href="{{ $sortUrl }}" class="inline-flex items-center gap-1.5 rounded-full border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-stone-700 shadow-sm" aria-label="{{ $sortLabel }}" title="{{ $sortLabel }}">
            {{ __('site.created_at') }}
            <span aria-hidden="true" class="text-base leading-none">{{ $direction === 'desc' ? '↓' : '↑' }}</span>
        </a>
        @forelse ($tickets as $ticket)
            <article class="rounded-[1.25rem] border border-stone-200 bg-white p-4 shadow-sm">
                <a class="inline-flex max-w-full items-center justify-center truncate rounded-full bg-olive-50 px-4 py-2 text-sm font-semibold text-olive-800 ring-1 ring-olive-200 transition hover:bg-olive-100" href="{{ route('admin.tickets.show', $ticket) }}" title="{{ $ticket->ticket_code }}">
                    {{ $ticket->ticket_code }}
                </a>
                <h2 class="mt-3 break-words font-semibold text-stone-950">{{ $ticket->localizedProjectName() }}</h2>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-stone-500">{{ __('site.created_at') }}</dt>
                        <dd class="font-semibold text-stone-900" title="{{ $ticket->created_at?->translatedFormat('M j, Y, g:i A') }}">{{ $ticket->created_at?->translatedFormat('M j, Y') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-stone-500">{{ __('site.form_service') }}</dt>
                        <dd class="font-semibold text-stone-900">{{ $ticket->serviceDisplayName() }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-stone-500">{{ __('site.current_stage') }}</dt>
                        <dd class="font-semibold text-stone-900">{{ $ticket->currentStage?->localizedName() ?? __('site.pending_assignment') }}</dd>
                    </div>
                </dl>
            </article>
        @empty
            <p class="rounded-[1.25rem] border border-stone-200 bg-white p-5 text-center text-stone-500">{{ __('site.no_projects_yet') }}</p>
        @endforelse
    </div>

    <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-[1180px] table-fixed text-left text-sm">
                <thead class="text-stone-500">
                    <tr>
                        <th class="w-52 pb-3">{{ __('site.form_ticket_code') }}</th>
                        <th class="w-80 pb-3">{{ __('site.form_project_name') }}</th>
                        <th class="w-64 pb-3">{{ __('site.form_email') }}</th>
                        <th class="w-64 pb-3">{{ __('site.form_service') }}</th>
                        <th class="w-44 pb-3">
                            <a href="{{ $sortUrl }}" class="inline-flex items-center gap-1.5 whitespace-nowrap font-semibold text-stone-700 hover:text-olive-800" aria-label="{{ $sortLabel }}" title="{{ $sortLabel }}" aria-sort="{{ $direction === 'desc' ? 'descending' : 'ascending' }}">
                                {{ __('site.created_at') }}
                                <span aria-hidden="true" class="text-base leading-none">{{ $direction === 'desc' ? '↓' : '↑' }}</span>
                            </a>
                        </th>
                        <th class="w-52 pb-3">{{ __('site.current_stage') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td class="py-4 pr-5">
                                <a class="inline-flex max-w-full items-center justify-center truncate rounded-full bg-olive-50 px-4 py-2 text-sm font-semibold text-olive-800 ring-1 ring-olive-200 transition hover:bg-olive-100" href="{{ route('admin.tickets.show', $ticket) }}" title="{{ $ticket->ticket_code }}">
                                    {{ $ticket->ticket_code }}
                                </a>
                            </td>
                            <td class="truncate py-4 pr-5" title="{{ $ticket->localizedProjectName() }}">{{ $ticket->localizedProjectName() }}</td>
                            <td class="truncate py-4 pr-5" title="{{ $ticket->email }}">{{ $ticket->email }}</td>
                            <td class="truncate py-4 pr-5" title="{{ $ticket->serviceDisplayName() }}">{{ $ticket->serviceDisplayName() }}</td>
                            <td class="py-4 pr-5" title="{{ $ticket->created_at?->translatedFormat('M j, Y, g:i A') }}">{{ $ticket->created_at?->translatedFormat('M j, Y') }}</td>
                            <td class="py-4 pr-5">{{ $ticket->currentStage?->localizedName() ?? __('site.pending_assignment') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-stone-500">{{ __('site.no_projects_yet') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8">
        {{ $tickets->links() }}
    </div>
@endsection
