@extends('layouts.panel', ['title' => __('site.admin_tickets'), 'heading' => __('site.admin_tickets')])

@section('content')
    <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-stone-500">
                    <tr>
                        <th class="pb-3">{{ __('site.form_ticket_code') }}</th>
                        <th class="pb-3">{{ __('site.form_project_name') }}</th>
                        <th class="pb-3">{{ __('site.form_email') }}</th>
                        <th class="pb-3">{{ __('site.form_service') }}</th>
                        <th class="pb-3">{{ __('site.current_stage') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($tickets as $ticket)
                        <tr>
                            <td class="py-3"><a class="font-semibold text-olive-700" href="{{ route('admin.tickets.show', $ticket) }}">{{ $ticket->ticket_code }}</a></td>
                            <td class="py-3">{{ $ticket->localizedProjectName() }}</td>
                            <td class="py-3">{{ $ticket->email }}</td>
                            <td class="py-3">{{ $ticket->service->localizedName() }}</td>
                            <td class="py-3">{{ $ticket->currentStage?->localizedName() ?? __('site.pending_assignment') }}</td>
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
