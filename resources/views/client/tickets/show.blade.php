@extends('layouts.panel', ['title' => $ticket->ticket_code, 'heading' => $ticket->localizedProjectName()])

@section('content')
    @php($clientDocumentCategories = ['payment_receipt', 'requested_document', 'supporting_document'])

    <div class="space-y-6">
        <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $ticket->ticket_code }}</p>
            <h2 class="mt-3 text-2xl font-semibold text-stone-950">{{ $ticket->serviceDisplayName() }}</h2>
            <p class="mt-4 text-base leading-7 text-stone-600">{{ $ticket->localizedProjectDescription() }}</p>
        </div>

        @include('partials.ticket-timeline', ['ticket' => $ticket, 'clientView' => true])

        <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-stone-950">{{ __('site.client_files') }}</h2>
            <div class="mt-5 space-y-5">
                @foreach ($ticket->deliverables as $deliverable)
                    @if ($deliverable->files->isNotEmpty())
                        <section class="rounded-2xl bg-stone-50 p-4">
                            <p class="font-semibold text-stone-950">{{ $deliverable->name }}</p>
                            <div class="mt-4 space-y-3">
                                @foreach ($deliverable->files as $file)
                                    @include('partials.ticket-file-card', [
                                        'file' => $file,
                                        'downloadUrl' => route('client.tickets.files.download', [$ticket, $file]),
                                    ])
                                @endforeach
                            </div>
                        </section>
                    @endif
                @endforeach
                @foreach ($ticket->files->whereNull('ticket_deliverable_id') as $file)
                    @include('partials.ticket-file-card', [
                        'file' => $file,
                        'downloadUrl' => route('client.tickets.files.download', [$ticket, $file]),
                    ])
                @endforeach
                @if ($ticket->files->whereNull('ticket_deliverable_id')->isEmpty() && $ticket->deliverables->every(fn ($deliverable) => $deliverable->files->isEmpty()))
                    <p class="text-sm text-stone-500">{{ __('site.no_client_files') }}</p>
                @endif
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[0.42fr_0.58fr]">
            <form method="POST" action="{{ route('client.tickets.documents.store', $ticket) }}" enctype="multipart/form-data" class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                @csrf
                <h2 class="text-lg font-semibold text-stone-950">{{ __('site.send_document') }}</h2>
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

            <section class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-stone-950">{{ __('site.documents_you_sent') }}</h2>
                <div class="mt-5 space-y-3">
                    @forelse ($submittedFiles as $file)
                        @include('partials.ticket-file-card', [
                            'file' => $file,
                            'downloadUrl' => route('client.tickets.files.download', [$ticket, $file]),
                        ])
                    @empty
                        <p class="text-sm text-stone-500">{{ __('site.no_documents_sent') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
