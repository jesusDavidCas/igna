@extends('layouts.panel', ['title' => $ticket->ticket_code, 'heading' => $ticket->ticket_code])

@section('content')
    <div class="grid gap-6 lg:grid-cols-[0.62fr_0.38fr]">
        <div class="space-y-6">
            <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-stone-950">{{ $ticket->localizedProjectName() }}</h2>
                <div class="mt-5 grid gap-4 text-sm text-stone-600 md:grid-cols-2">
                    <p><span class="font-semibold text-stone-900">{{ __('site.form_service') }}:</span> {{ $ticket->service->localizedName() }}</p>
                    <p><span class="font-semibold text-stone-900">{{ __('site.form_email') }}:</span> {{ $ticket->email }}</p>
                    <p><span class="font-semibold text-stone-900">{{ __('site.form_phone') }}:</span> {{ $ticket->phone ?: '—' }}</p>
                    <p><span class="font-semibold text-stone-900">{{ __('site.current_stage') }}:</span> {{ $ticket->currentStage?->localizedName() ?? __('site.pending_assignment') }}</p>
                    <p><span class="font-semibold text-stone-900">{{ __('site.assigned_client') }}:</span> {{ $ticket->client?->name ?? __('site.unassigned') }}</p>
                    <p><span class="font-semibold text-stone-900">{{ __('site.form_project_location') }}:</span> {{ $ticket->project_location ?: '—' }}</p>
                    <p><span class="font-semibold text-stone-900">{{ __('site.form_target_date') }}:</span> {{ optional($ticket->target_date)->format('Y-m-d') ?: '—' }}</p>
                </div>
                <div class="mt-5 rounded-2xl bg-stone-50 p-4 text-sm leading-7 text-stone-700">
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
                <p class="mt-2 text-sm text-stone-500">{{ __('site.assign_client_help') }}</p>
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

            <form method="POST" action="{{ route('admin.tickets.stage.update', $ticket) }}" class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                @csrf
                @method('PUT')
                <h2 class="text-lg font-semibold text-stone-950">{{ __('site.update_stage') }}</h2>
                <div class="mt-5 space-y-4">
                    <div>
                        <label class="form-label">{{ __('site.current_stage') }}</label>
                        <select name="service_stage_id" class="form-input" required>
                            @foreach ($ticket->service->stages as $stage)
                                <option value="{{ $stage->id }}" @selected($ticket->current_service_stage_id === $stage->id)>{{ $stage->sort_order }}. {{ $stage->localizedName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">{{ __('site.form_notes') }}</label>
                        <textarea name="notes" rows="4" class="form-input"></textarea>
                    </div>
                    <button type="submit" class="rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.save_changes') }}</button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.tickets.files.store', $ticket) }}" enctype="multipart/form-data" class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                @csrf
                <h2 class="text-lg font-semibold text-stone-950">{{ __('site.upload_file') }}</h2>
                <p class="mt-2 text-sm text-stone-500">{{ __('site.upload_file_help') }}</p>
                <div class="mt-5 space-y-4">
                    <div>
                        <label class="form-label">{{ __('site.form_title') }}</label>
                        <input name="title" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">{{ __('site.form_deliverable_type') }}</label>
                        <input name="deliverable_type" class="form-input">
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
                <h2 class="text-lg font-semibold text-stone-950">{{ __('site.files') }}</h2>
                <div class="mt-5 space-y-4">
                    @forelse ($ticket->files as $file)
                        <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-stone-900">{{ $file->title }}</p>
                                    <p class="mt-1 text-stone-500">{{ $file->original_name }}</p>
                                    <div class="mt-3 flex flex-wrap items-center gap-3">
                                        <p class="text-xs uppercase tracking-[0.18em] text-stone-400">{{ $file->storageProviderLabel() }}</p>
                                        <a href="{{ route('admin.tickets.files.download', [$ticket, $file]) }}" class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-olive-700 ring-1 ring-olive-200 transition hover:bg-olive-50">{{ __('site.download_file') }}</a>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('admin.tickets.files.visibility.update', [$ticket, $file]) }}">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="rounded-full px-3 py-1 text-xs font-semibold {{ $file->is_client_visible ? 'bg-emerald-50 text-emerald-800' : 'bg-stone-200 text-stone-600' }}">
                                        {{ $file->is_client_visible ? __('site.client_visible') : __('site.internal_only') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-stone-500">{{ __('site.no_files_uploaded') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
