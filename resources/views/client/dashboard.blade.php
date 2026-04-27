@extends('layouts.panel', ['title' => __('site.client_dashboard'), 'heading' => __('site.client_dashboard')])

@section('content')
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($tickets as $ticket)
            <a href="{{ route('client.tickets.show', $ticket) }}" class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $ticket->ticket_code }}</p>
                <h2 class="mt-3 text-xl font-semibold text-stone-950">{{ $ticket->localizedProjectName() }}</h2>
                <p class="mt-2 text-sm text-stone-600">{{ $ticket->service->localizedName() }}</p>
                <p class="mt-4 text-sm text-olive-700">{{ __('site.current_stage') }}: {{ $ticket->currentStage?->localizedName() ?? __('site.pending_assignment') }}</p>
            </a>
        @empty
            <div class="rounded-[2rem] border border-dashed border-stone-300 bg-stone-50 p-8 text-sm text-stone-500 md:col-span-2 xl:col-span-3">
                {{ __('site.client_no_tickets') }}
            </div>
        @endforelse
    </div>
@endsection
