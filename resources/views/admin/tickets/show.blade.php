@extends('layouts.panel', ['title' => $ticket->ticket_code, 'heading' => $ticket->ticket_code])

@section('content')
    @php
        $adminFileCategories = ['proposal', 'invoice', 'bank_information', 'payment_instructions', 'agreement', 'project_document', 'other'];
        $generalProjectFiles = $ticket->files->filter(fn ($file) => ! $file->isClientSubmitted() && $file->ticket_deliverable_id === null);
        $clientSubmittedFiles = $ticket->files->filter(fn ($file) => $file->isClientSubmitted());
    @endphp

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
        <div class="space-y-6">
            <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-stone-950">{{ $ticket->localizedProjectName() }}</h2>
                <div class="mt-5 grid gap-4 text-[15px] text-stone-600 md:grid-cols-2">
                    <p><span class="font-semibold text-stone-900">{{ __('site.form_service') }}:</span> {{ $ticket->serviceDisplayName() }}</p>
                    <p><span class="font-semibold text-stone-900">{{ __('site.form_email') }}:</span> {{ $ticket->email }}</p>
                    <p><span class="font-semibold text-stone-900">{{ __('site.form_phone') }}:</span> {{ $ticket->phone ?: '-' }}</p>
                    <p><span class="font-semibold text-stone-900">{{ __('site.current_stage') }}:</span> {{ $ticket->currentStage?->localizedName() ?? __('site.pending_assignment') }}</p>
                    <p><span class="font-semibold text-stone-900">{{ __('site.assigned_client') }}:</span> {{ $ticket->client?->name ?? __('site.unassigned') }}</p>
                    <p><span class="font-semibold text-stone-900">{{ __('site.form_project_location') }}:</span> {{ $ticket->project_location ?: '-' }}</p>
                    <p><span class="font-semibold text-stone-900">{{ __('site.form_target_date') }}:</span> {{ optional($ticket->target_date)->format('Y-m-d') ?: '-' }}</p>
                </div>
                <div class="mt-5 rounded-2xl bg-stone-50 p-4 text-base leading-7 text-stone-700">
                    {{ $ticket->localizedProjectDescription() }}
                </div>
            </div>

            @include('partials.ticket-timeline', ['ticket' => $ticket, 'clientView' => false])

            <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm" data-section="deliverables-wide">
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-stone-950">{{ __('site.deliverables') }}</h2>
                        <p class="mt-2 text-[15px] leading-6 text-stone-500">{{ __('site.scope_deliverables') }}</p>
                    </div>
                </div>
                <div class="mt-5 space-y-4" data-layout="deliverables-stacked">
                    @forelse ($ticket->deliverables as $deliverable)
                        <article class="w-full rounded-2xl border border-stone-200 bg-white p-5 shadow-sm" data-deliverable-section>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <p class="break-words font-semibold text-stone-950">{{ $deliverable->name }}</p>
                                    @if ($deliverable->description)
                                        <p class="mt-2 text-[15px] leading-6 text-stone-600">{{ $deliverable->description }}</p>
                                    @endif
                                </div>
                                <span class="w-fit rounded-full bg-stone-50 px-3 py-1 text-sm font-semibold text-stone-700 ring-1 ring-stone-200">{{ $deliverable->statusLabel() }}</span>
                            </div>

                            <form method="POST" action="{{ route('admin.tickets.files.store', $ticket) }}" enctype="multipart/form-data" class="mt-5 rounded-2xl border border-stone-200 bg-stone-50 p-4">
                                @csrf
                                <input type="hidden" name="ticket_deliverable_id" value="{{ $deliverable->id }}">
                                <input type="hidden" name="deliverable_type" value="project_document">
                                <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_12rem_minmax(0,1fr)] md:items-end">
                                    <div>
                                        <label class="form-label">{{ __('site.form_title') }}</label>
                                        <input name="title" class="form-input" required>
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
                                    <label class="inline-flex items-center gap-2 text-sm text-stone-700 md:col-span-2">
                                        <input type="checkbox" name="is_client_visible" value="1">
                                        {{ __('site.client_visible') }}
                                    </label>
                                    <button type="submit" class="w-fit rounded-full border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-stone-700">{{ __('site.upload_file') }}</button>
                                </div>
                            </form>

                            <div class="mt-5 space-y-3">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">{{ __('site.deliverable_documents') }}</h3>
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
                        </article>
                    @empty
                        <p class="text-sm text-stone-500">{{ __('site.no_files_uploaded') }}</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
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

            <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-stone-950">{{ __('site.general_project_files') }}</h2>
                <p class="mt-2 text-[15px] leading-6 text-stone-500">{{ __('site.administrator_file_policy') }}</p>
                <form method="POST" action="{{ route('admin.tickets.files.store', $ticket) }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf
                    <input type="hidden" name="ticket_deliverable_id" value="">
                    <input type="hidden" name="delivery_type" value="internal">
                    <div>
                        <label class="form-label">{{ __('site.form_title') }}</label>
                        <input name="title" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">{{ __('site.category') }}</label>
                        <select name="deliverable_type" class="form-input" required>
                            @foreach ($adminFileCategories as $category)
                                <option value="{{ $category }}">{{ __("site.ticket_file_category_{$category}") }}</option>
                            @endforeach
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
                </form>
                <div class="mt-5 space-y-3">
                    @forelse ($generalProjectFiles as $file)
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
            </section>

            <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-stone-950">{{ __('site.stage_completion_control') }}</h2>
                <p class="mt-2 text-[15px] leading-6 text-stone-500">{{ __('site.stage_completion_help') }}</p>
                <div class="mt-5 space-y-4">
                    @foreach ($ticket->stageEvents->sortBy(fn ($event) => $event->serviceStage->sort_order) as $event)
                        <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                            <p class="font-semibold text-stone-950">{{ $event->serviceStage->localizedName() }}</p>
                            <p class="mt-1 text-xs uppercase tracking-[0.18em] text-stone-400">{{ $event->status->label() }}</p>
                            <p class="mt-2 text-sm leading-6 text-stone-500">
                                {{ __('site.timeline_started') }}: {{ optional($event->entered_at)->format('Y-m-d H:i') ?: __('site.pending_date') }}
                                · {{ __('site.timeline_finished') }}: {{ optional($event->completed_at)->format('Y-m-d H:i') ?: __('site.pending_date') }}
                            </p>
                            @if ($event->status === \App\Enums\StageEventStatus::CURRENT)
                                <form method="POST" action="{{ route('admin.tickets.stages.complete', [$ticket, $event]) }}" class="mt-3" data-confirm-title="{{ __('site.confirm_stage_complete_title') }}" data-confirm-message="{{ __('site.confirm_stage_complete_message') }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="stage_event_id" value="{{ $event->id }}">
                                    <textarea name="notes" rows="2" class="form-input text-sm" placeholder="{{ __('site.completion_note_optional') }}"></textarea>
                                    <button type="submit" class="mt-2 rounded-full border border-olive-300 px-3 py-1.5 text-sm font-semibold text-olive-800">{{ __('site.mark_stage_completed') }}</button>
                                </form>
                            @elseif ($event->status === \App\Enums\StageEventStatus::COMPLETED && $previousStage?->id === $event->service_stage_id)
                                <form method="POST" action="{{ route('admin.tickets.stages.reopen', [$ticket, $event]) }}" class="mt-3" data-confirm-title="{{ __('site.confirm_stage_reopen_title') }}" data-confirm-message="{{ __('site.confirm_stage_reopen_message') }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="stage_event_id" value="{{ $event->id }}">
                                    <textarea name="notes" rows="2" class="form-input text-sm" placeholder="{{ __('site.rollback_reason') }}"></textarea>
                                    <button type="submit" class="mt-2 rounded-full border border-amber-300 px-3 py-1.5 text-sm font-semibold text-amber-800">{{ __('site.reopen_previous_stage') }}</button>
                                </form>
                            @elseif ($event->status === \App\Enums\StageEventStatus::COMPLETED)
                                <p class="mt-3 rounded-xl bg-stone-100 px-3 py-2 text-sm leading-6 text-stone-600">{{ __('site.stage_cannot_reopen_until_previous') }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-stone-950">{{ __('site.client_documents_received') }}</h2>
                <div class="mt-5 space-y-3">
                    @forelse ($clientSubmittedFiles as $file)
                        @include('partials.ticket-file-card', [
                            'file' => $file,
                            'downloadUrl' => route('admin.tickets.files.download', [$ticket, $file]),
                            'reviewRoute' => route('admin.tickets.files.review.update', [$ticket, $file]),
                            'rejectRoute' => route('admin.tickets.files.reject.update', [$ticket, $file]),
                            'deleteRoute' => route('admin.tickets.files.destroy', [$ticket, $file]),
                        ])
                    @empty
                        <p class="text-sm text-stone-500">{{ __('site.no_documents_sent') }}</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
@endsection
