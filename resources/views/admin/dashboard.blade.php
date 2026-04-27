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
        <h2 class="text-lg font-semibold text-stone-950">{{ __('site.recent_requests') }}</h2>
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
                            <td class="py-3">{{ $ticket->service->localizedName() }}</td>
                            <td class="py-3">{{ $ticket->currentStage?->localizedName() ?? __('site.pending_assignment') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
