@extends('layouts.panel', ['title' => __('site.admin_dashboard'), 'heading' => __('site.admin_dashboard')])

@section('content')
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $label => $value)
            <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                <p class="text-sm text-stone-500">{{ __("site.stat_{$label}") }}</p>
                <p class="mt-3 text-3xl font-semibold text-stone-950">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <h2 class="text-lg font-semibold text-stone-950">{{ __('site.recent_requests') }}</h2>
            <a href="{{ route('admin.proposals.create') }}" class="rounded-full bg-olive-700 px-4 py-2 text-sm font-semibold text-white">{{ __('site.new_proposal') }}</a>
        </div>
        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-stone-500">
                    <tr>
                        <th class="pb-3">{{ __('site.form_ticket_code') }}</th>
                        <th class="pb-3">{{ __('site.form_project_name') }}</th>
                        <th class="pb-3">{{ __('site.form_service') }}</th>
                        <th class="pb-3">{{ __('site.current_stage') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($recentTickets as $ticket)
                        <tr>
                            <td class="py-3"><a class="font-semibold text-olive-700" href="{{ route('admin.tickets.show', $ticket) }}">{{ $ticket->ticket_code }}</a></td>
                            <td class="py-3">{{ $ticket->localizedProjectName() }}</td>
                            <td class="py-3">{{ $ticket->serviceDisplayName() }}</td>
                            <td class="py-3">{{ $ticket->currentStage?->localizedName() ?? __('site.pending_assignment') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <h2 class="text-lg font-semibold text-stone-950">{{ __('site.recent_proposals') }}</h2>
            <a href="{{ route('admin.proposals.index') }}" class="text-sm font-semibold text-olive-700">{{ __('site.view_all_proposals') }}</a>
        </div>
        <div class="mt-5 grid gap-4 md:grid-cols-2">
            @forelse ($recentProposals as $proposal)
                <a href="{{ route('admin.proposals.show', $proposal) }}" class="rounded-2xl border border-stone-200 bg-stone-50 p-4 transition hover:border-olive-300 hover:bg-olive-50/40">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $proposal->proposal_number }}</p>
                    <h3 class="mt-2 font-semibold text-stone-950">{{ $proposal->localizedTitle() }}</h3>
                    <p class="mt-1 text-sm text-stone-500">{{ $proposal->client?->name ?? __('site.unassigned') }} · {{ $proposal->statusLabel() }}</p>
                    <p class="mt-3 text-sm font-semibold text-olive-800">{{ number_format((float) $proposal->total, 2) }}</p>
                </a>
            @empty
                <p class="text-sm text-stone-500">{{ __('site.no_proposals_yet') }}</p>
            @endforelse
        </div>
    </div>
@endsection
