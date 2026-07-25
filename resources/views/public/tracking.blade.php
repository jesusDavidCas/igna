@extends('layouts.public')

@section('content')
    @php($clientDocumentCategories = ['payment_receipt', 'requested_document', 'supporting_document'])

    <section class="mx-auto max-w-7xl px-6 py-16 lg:px-8">
        <div class="max-w-3xl">
            <p class="section-eyebrow">{{ __('site.nav_tracking') }}</p>
            <h1 class="section-title">{{ __('site.tracking_title') }}</h1>
            <p class="section-copy">{{ __('site.tracking_intro') }}</p>
        </div>

        <div class="mt-10 grid gap-8 lg:grid-cols-[0.42fr_0.58fr]">
            <form method="POST" action="{{ route('tracking.show') }}" class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
                @csrf
                <div class="space-y-5">
                    <div>
                        <label class="form-label">{{ __('site.form_ticket_code') }}</label>
                        <input name="ticket_code" value="{{ old('ticket_code', session('tracking_lookup.ticket_code')) }}" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">{{ __('site.form_email') }}</label>
                        <input type="email" name="email" value="{{ old('email', session('tracking_lookup.email')) }}" class="form-input" required>
                    </div>
                    <button type="submit" class="rounded-full bg-olive-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-olive-800">
                        {{ __('site.cta_track') }}
                    </button>
                </div>
            </form>

            <div class="space-y-6">
                @if ($ticket)
                    <div class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
                        <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $ticket->ticket_code }}</p>
                                <h2 class="mt-3 text-3xl font-semibold text-stone-950">{{ $ticket->localizedProjectName() }}</h2>
                                <p class="mt-2 text-base text-stone-600">{{ $ticket->service->localizedName() }}</p>
                            </div>
                            <div class="rounded-2xl bg-olive-50 px-4 py-3 text-[15px] text-olive-900">
                                <p class="font-semibold">{{ __('site.current_stage') }}</p>
                                <p class="mt-1">{{ $ticket->currentStage?->localizedName() ?? __('site.pending_assignment') }}</p>
                            </div>
                        </div>
                    </div>

                    @include('partials.ticket-timeline', ['ticket' => $ticket, 'clientView' => true])

                    <div class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
                        <h3 class="text-lg font-semibold text-stone-950">{{ __('site.client_files') }}</h3>
                        <div class="mt-5 space-y-5">
                            @foreach ($ticket->deliverables as $deliverable)
                                @if ($deliverable->files->isNotEmpty())
                                    <section class="rounded-2xl bg-stone-50 p-4">
                                        <p class="font-semibold text-stone-950">{{ $deliverable->name }}</p>
                                        <div class="mt-4 space-y-3">
                                            @foreach ($deliverable->files as $file)
                                                @include('partials.ticket-file-card', [
                                                    'file' => $file,
                                                    'downloadUrl' => URL::temporarySignedRoute('tracking.files.download', now()->addMinutes(30), [
                                                        'ticket' => $ticket,
                                                        'file' => $file,
                                                        'email_hash' => hash('sha256', strtolower($ticket->email)),
                                                    ]),
                                                ])
                                            @endforeach
                                        </div>
                                    </section>
                                @endif
                            @endforeach
                            @foreach ($ticket->files->whereNull('ticket_deliverable_id') as $file)
                                @include('partials.ticket-file-card', [
                                    'file' => $file,
                                    'downloadUrl' => URL::temporarySignedRoute('tracking.files.download', now()->addMinutes(30), [
                                        'ticket' => $ticket,
                                        'file' => $file,
                                        'email_hash' => hash('sha256', strtolower($ticket->email)),
                                    ]),
                                ])
                            @endforeach
                            @if ($ticket->files->whereNull('ticket_deliverable_id')->isEmpty() && $ticket->deliverables->every(fn ($deliverable) => $deliverable->files->isEmpty()))
                                <p class="text-sm text-stone-500">{{ __('site.no_client_files') }}</p>
                            @endif
                        </div>
                    </div>

                    @if ($trackingUploadUrl)
                        <div class="grid gap-6 lg:grid-cols-2">
                            <form method="POST" action="{{ $trackingUploadUrl }}" enctype="multipart/form-data" class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
                                @csrf
                                <h3 class="text-lg font-semibold text-stone-950">{{ __('site.send_document') }}</h3>
                                <p class="mt-2 text-[15px] leading-6 text-stone-500">{{ __('site.client_upload_policy') }}</p>
                                <div class="mt-5 space-y-4">
                                    <div>
                                        <label class="form-label">{{ __('site.document_category') }}</label>
                                        <select name="category" class="form-input" required>
                                            @foreach ($clientDocumentCategories as $category)
                                                <option value="{{ $category }}">{{ __("site.ticket_file_category_{$category}") }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">{{ __('site.document_file') }}</label>
                                        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="form-input" required>
                                    </div>
                                    <button type="submit" class="rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.send_document') }}</button>
                                </div>
                            </form>

                            <section class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
                                <h3 class="text-lg font-semibold text-stone-950">{{ __('site.documents_you_sent') }}</h3>
                                <div class="mt-5 space-y-3">
                                    @forelse ($submittedFiles as $file)
                                        @include('partials.ticket-file-card', [
                                            'file' => $file,
                                            'downloadUrl' => URL::temporarySignedRoute('tracking.files.download', now()->addMinutes(30), [
                                                'ticket' => $ticket,
                                                'file' => $file,
                                                'email_hash' => hash('sha256', strtolower($ticket->email)),
                                            ]),
                                        ])
                                    @empty
                                        <p class="text-sm text-stone-500">{{ __('site.no_documents_sent') }}</p>
                                    @endforelse
                                </div>
                            </section>
                        </div>
                    @endif
                @else
                    <div class="rounded-[2rem] border border-dashed border-stone-300 bg-stone-50 p-8 text-base leading-7 text-stone-500">
                        {{ __('site.tracking_empty') }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
