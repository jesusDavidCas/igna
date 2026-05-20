@extends('layouts.panel', ['title' => __('site.admin_tickets'), 'heading' => __('site.admin_tickets')])

@section('content')
    <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[1180px] table-fixed text-left text-sm">
                <thead class="text-stone-500">
                    <tr>
                        <th class="w-52 pb-3">{{ __('site.form_ticket_code') }}</th>
                        <th class="w-80 pb-3">{{ __('site.form_project_name') }}</th>
                        <th class="w-64 pb-3">{{ __('site.form_email') }}</th>
                        <th class="w-64 pb-3">{{ __('site.form_service') }}</th>
                        <th class="w-52 pb-3">{{ __('site.current_stage') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($tickets as $ticket)
                        <tr>
                            <td class="py-4 pr-5">
                                <a class="inline-flex max-w-full items-center justify-center truncate rounded-full bg-olive-50 px-4 py-2 text-sm font-semibold text-olive-800 ring-1 ring-olive-200 transition hover:bg-olive-100" href="{{ route('admin.tickets.show', $ticket) }}" title="{{ $ticket->ticket_code }}">
                                    {{ $ticket->ticket_code }}
                                </a>
                            </td>
                            <td class="truncate py-4 pr-5" title="{{ $ticket->localizedProjectName() }}">{{ $ticket->localizedProjectName() }}</td>
                            <td class="truncate py-4 pr-5" title="{{ $ticket->email }}">{{ $ticket->email }}</td>
                            <td class="truncate py-4 pr-5" title="{{ $ticket->service->localizedName() }}">{{ $ticket->service->localizedName() }}</td>
                            <td class="py-4 pr-5">{{ $ticket->currentStage?->localizedName() ?? __('site.pending_assignment') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8">
        {{ $tickets->links() }}
    </div>
@endsection
