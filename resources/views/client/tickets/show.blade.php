@extends('layouts.panel', ['title' => $ticket->ticket_code, 'heading' => $ticket->localizedProjectName()])

@section('content')
    <div class="space-y-6">
        <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $ticket->ticket_code }}</p>
            <h2 class="mt-3 text-2xl font-semibold text-stone-950">{{ $ticket->service->localizedName() }}</h2>
            <p class="mt-4 text-sm leading-7 text-stone-600">{{ $ticket->localizedProjectDescription() }}</p>
        </div>

        @include('partials.ticket-timeline', ['ticket' => $ticket, 'clientView' => true])

        <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-stone-950">{{ __('site.client_files') }}</h2>
            <div class="mt-5 space-y-4">
                @forelse ($ticket->files as $file)
                    @include('partials.ticket-file-card', [
                        'file' => $file,
                        'downloadUrl' => route('client.tickets.files.download', [$ticket, $file]),
                    ])
                @empty
                    <p class="text-sm text-stone-500">{{ __('site.no_client_files') }}</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
