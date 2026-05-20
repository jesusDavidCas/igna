@extends('layouts.panel', ['title' => $ticket->ticket_code, 'heading' => $ticket->ticket_code])

@section('content')
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
        <div class="space-y-6">
            <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-stone-950">{{ $ticket->localizedProjectName() }}</h2>
                <div class="mt-5 grid gap-4 text-[15px] text-stone-600 md:grid-cols-2">
                    <p><span class="font-semibold text-stone-900">{{ __('site.form_service') }}:</span> {{ $ticket->service->localizedName() }}</p>
                    <p><span class="font-semibold text-stone-900">{{ __('site.form_email') }}:</span> {{ $ticket->email }}</p>
                    <p><span class="font-semibold text-stone-900">{{ __('site.form_phone') }}:</span> {{ $ticket->phone ?: '—' }}</p>
                    <p><span class="font-semibold text-stone-900">{{ __('site.current_stage') }}:</span> {{ $ticket->currentStage?->localizedName() ?? __('site.pending_assignment') }}</p>
                    <p><span class="font-semibold text-stone-900">{{ __('site.assigned_client') }}:</span> {{ $ticket->client?->name ?? __('site.unassigned') }}</p>
                    <p><span class="font-semibold text-stone-900">{{ __('site.form_project_location') }}:</span> {{ $ticket->project_location ?: '—' }}</p>
                    <p><span class="font-semibold text-stone-900">{{ __('site.form_target_date') }}:</span> {{ optional($ticket->target_date)->format('Y-m-d') ?: '—' }}</p>
                </div>
                <div class="mt-5 rounded-2xl bg-stone-50 p-4 text-base leading-7 text-stone-700">
                    {{ $ticket->localizedProjectDescription() }}
                </div>
            </div>

            @include('partials.ticket-timeline', ['ticket' => $ticket, 'clientView' => false])
        </div>

        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.tickets.client.update', $ticket) }}" class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                @csrf
                @method('PUT')
                <h2 class="text-lg font-semibold text-stone-950">{{ __('site.assign_client') }}</h2>
                <p class="mt-2 text-[15px] leading-6 text-stone-500">{{ __('site.assign_client_help') }}</p>
                <div class="mt-5 space-y-4">
                    <div>
                        <label class="form-label">{{ __('site.client_account') }}</label>
                        <select name="client_user_id" class="form-input">
                            <option value="">{{ __('site.unassigned') }}</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" @selected($ticket->client_user_id === $client->id)>
                                    {{ $client->name }} · {{ $client->email }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="rounded-full border border-stone-300 px-5 py-2.5 text-sm font-semibold text-stone-700">{{ __('site.save_changes') }}</button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.tickets.stage.update', $ticket) }}" class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm" data-confirm-title="{{ __('site.confirm_stage_change_title') }}" data-confirm-message="{{ __('site.confirm_stage_change_message') }}">
                @csrf
                @method('PUT')
                <h2 class="text-lg font-semibold text-stone-950">{{ __('site.update_stage') }}</h2>
                <p class="mt-2 text-[15px] leading-6 text-stone-500">{{ __('site.update_stage_help') }}</p>
                <div class="mt-5 space-y-4">
                    <div>
                        <label class="form-label">{{ __('site.next_stage') }}</label>
                        <select name="service_stage_id" class="form-input" required>
                            @if ($nextStage)
                                <option value="{{ $nextStage->id }}">{{ $nextStage->sort_order }}. {{ $nextStage->localizedName() }}</option>
                            @else
                                <option value="">{{ __('site.no_next_stage') }}</option>
                            @endif
                        </select>
                    </div>
                    <div>
                        <label class="form-label">{{ __('site.form_notes') }}</label>
                        <textarea name="notes" rows="4" class="form-input"></textarea>
                    </div>
                    <button type="submit" @disabled(! $nextStage) class="rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:bg-stone-300">{{ __('site.confirm_change') }}</button>
                </div>
            </form>

            @if ($previousStage)
                <form method="POST" action="{{ route('admin.tickets.stage.back', $ticket) }}" class="rounded-[2rem] border border-amber-200 bg-amber-50 p-6 shadow-sm" data-confirm-title="{{ __('site.confirm_stage_back_title') }}" data-confirm-message="{{ __('site.confirm_stage_back_message') }}">
                    @csrf
                    @method('PUT')
                    <h2 class="text-lg font-semibold text-stone-950">{{ __('site.move_back_stage') }}</h2>
                    <p class="mt-2 text-[15px] leading-6 text-stone-600">{{ __('site.move_back_stage_help', ['stage' => $previousStage->localizedName()]) }}</p>
                    <input type="hidden" name="service_stage_id" value="{{ $previousStage->id }}">
                    <textarea name="notes" rows="3" class="form-input mt-4" placeholder="{{ __('site.correction_note_optional') }}"></textarea>
                    <button type="submit" class="mt-4 rounded-full border border-amber-400 bg-white px-5 py-2.5 text-sm font-semibold text-amber-900">{{ __('site.move_back_stage') }}</button>
                </form>
            @endif

            <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-stone-950">{{ __('site.stage_completion_control') }}</h2>
                <p class="mt-2 text-[15px] leading-6 text-stone-500">{{ __('site.stage_completion_help') }}</p>
                <div class="mt-5 space-y-4">
                    @foreach ($ticket->stageEvents->sortBy(fn ($event) => $event->serviceStage->sort_order) as $event)
                        <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p class="font-semibold text-stone-950">{{ $event->serviceStage->localizedName() }}</p>
                                    <p class="mt-1 text-xs uppercase tracking-[0.18em] text-stone-400">{{ $event->status->label() }}</p>
                                    <p class="mt-2 text-sm leading-6 text-stone-500">
                                        {{ __('site.timeline_started') }}: {{ optional($event->entered_at)->format('Y-m-d H:i') ?: __('site.pending_date') }}
                                        · {{ __('site.timeline_finished') }}: {{ optional($event->completed_at)->format('Y-m-d H:i') ?: __('site.pending_date') }}
                                    </p>
                                </div>
                                @if ($event->status !== \App\Enums\StageEventStatus::COMPLETED)
                                    <form method="POST" action="{{ route('admin.tickets.stages.complete', [$ticket, $event]) }}" class="min-w-0 md:w-64" data-confirm-title="{{ __('site.confirm_stage_complete_title') }}" data-confirm-message="{{ __('site.confirm_stage_complete_message') }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="stage_event_id" value="{{ $event->id }}">
                                        <textarea name="notes" rows="2" class="form-input text-sm" placeholder="{{ __('site.completion_note_optional') }}"></textarea>
                                        <button type="submit" class="mt-2 rounded-full border border-olive-300 px-3 py-1.5 text-sm font-semibold text-olive-800">{{ __('site.mark_stage_completed') }}</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.tickets.stages.reopen', [$ticket, $event]) }}" data-confirm-title="{{ __('site.confirm_stage_reopen_title') }}" data-confirm-message="{{ __('site.confirm_stage_reopen_message') }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="stage_event_id" value="{{ $event->id }}">
                                        <button type="submit" class="rounded-full border border-amber-300 px-3 py-1.5 text-sm font-semibold text-amber-800">{{ __('site.reopen_stage') }}</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <form method="POST" action="{{ route('admin.tickets.files.store', $ticket) }}" enctype="multipart/form-data" class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                @csrf
                <h2 class="text-lg font-semibold text-stone-950">{{ __('site.upload_file') }}</h2>
                <p class="mt-2 text-[15px] leading-6 text-stone-500">{{ __('site.upload_file_help') }}</p>
                <div class="mt-5 space-y-4">
                    <div>
                        <label class="form-label">{{ __('site.deliverable') }}</label>
                        <select name="ticket_deliverable_id" class="form-input">
                            <option value="">{{ __('site.general_files') }}</option>
                            @foreach ($ticket->deliverables as $deliverable)
                                <option value="{{ $deliverable->id }}">{{ $deliverable->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">{{ __('site.form_title') }}</label>
                        <input name="title" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">{{ __('site.form_deliverable_type') }}</label>
                        <input name="deliverable_type" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">{{ __('site.delivery_type') }}</label>
                        <select name="delivery_type" class="form-input" required>
                            <option value="internal">{{ __('site.delivery_type_internal') }}</option>
                            <option value="partial">{{ __('site.delivery_type_partial') }}</option>
                            <option value="final">{{ __('site.delivery_type_final') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">{{ __('site.form_file') }}</label>
                        <input type="file" name="file" class="form-input" required>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-stone-700">
                        <input type="checkbox" name="is_client_visible" value="1">
                        {{ __('site.client_visible') }}
                    </label>
                    <button type="submit" class="rounded-full border border-stone-300 px-5 py-2.5 text-sm font-semibold text-stone-700">{{ __('site.upload_file') }}</button>
                </div>
            </form>

            <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-stone-950">{{ __('site.deliverables') }}</h2>
                <div class="mt-5 space-y-5">
                    @foreach ($ticket->deliverables as $deliverable)
                        <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p class="font-semibold text-stone-950">{{ $deliverable->name }}</p>
                                    @if ($deliverable->description)
                                        <p class="mt-1 text-base leading-7 text-stone-600">{{ $deliverable->description }}</p>
                                    @endif
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-stone-700 ring-1 ring-stone-200">{{ $deliverable->statusLabel() }}</span>
                            </div>
                            <div class="mt-4 space-y-3">
                                @forelse ($deliverable->files as $file)
                                    @include('partials.ticket-file-card', [
                                        'file' => $file,
                                        'downloadUrl' => route('admin.tickets.files.download', [$ticket, $file]),
                                        'visibilityRoute' => route('admin.tickets.files.visibility.update', [$ticket, $file]),
                                        'deleteRoute' => route('admin.tickets.files.destroy', [$ticket, $file]),
                                    ])
                                @empty
                                    <p class="text-sm text-stone-500">{{ __('site.no_files_uploaded') }}</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach

                    @php
                        $generalFiles = $ticket->files->whereNull('ticket_deliverable_id');
                    @endphp
                    @if ($generalFiles->isNotEmpty())
                        <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                            <p class="font-semibold text-stone-950">{{ __('site.general_files') }}</p>
                            <div class="mt-4 space-y-3">
                                @foreach ($generalFiles as $file)
                                    @include('partials.ticket-file-card', [
                                        'file' => $file,
                                        'downloadUrl' => route('admin.tickets.files.download', [$ticket, $file]),
                                        'visibilityRoute' => route('admin.tickets.files.visibility.update', [$ticket, $file]),
                                        'deleteRoute' => route('admin.tickets.files.destroy', [$ticket, $file]),
                                    ])
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if ($ticket->deliverables->isEmpty() && $generalFiles->isEmpty())
                        <p class="text-sm text-stone-500">{{ __('site.no_files_uploaded') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
