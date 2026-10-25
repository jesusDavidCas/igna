@extends('layouts.panel', ['title' => __('site.bulk_delete_preview_title'), 'heading' => __('site.bulk_delete_preview_title')])

@section('content')
    <section class="mx-auto max-w-5xl rounded-2xl border border-rose-200 bg-white p-5 shadow-sm sm:p-7">
        <p class="max-w-3xl text-sm leading-6 text-stone-700">{{ __('site.bulk_delete_preview_intro', ['count' => $tickets->count()]) }}</p>
        @if ($isAllMatching)
            <p class="mt-3 max-w-3xl text-sm font-semibold leading-6 text-rose-800">{{ __('site.bulk_delete_all_matching_preview_warning', ['count' => $tickets->count()]) }}</p>
        @endif
        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
            <h2 class="text-sm font-semibold text-amber-950">{{ __('site.deletion_dependency_counts') }}</h2>
            <ul class="mt-2 grid gap-1 text-sm text-amber-900 sm:grid-cols-2">
                @foreach ($totalImpactCounts as $key => $value)<li>{{ __('site.deletion_count_'.$key) }}: {{ $value }}</li>@endforeach
            </ul>
        </div>
        <div class="mt-6 space-y-5">
            @foreach ($tickets as $ticket)
                @php($impact = $impacts->get($ticket->id))
                <article class="rounded-xl border border-stone-200 p-4 sm:p-5"><div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-start"><div><p class="text-sm font-semibold text-olive-800">{{ $ticket->ticket_code }}</p><h2 class="mt-1 text-lg font-semibold text-stone-950">{{ $ticket->localizedProjectName() }}</h2><p class="mt-1 break-all text-sm text-stone-600">{{ $ticket->email }}</p></div><p class="text-sm text-stone-600">{{ $ticket->currentStage?->localizedName() ?? __('site.pending_assignment') }}</p></div>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3"><div><dt class="text-stone-500">{{ __('site.bulk_delete_status') }}</dt><dd class="font-medium text-stone-900">{{ str($ticket->getRawOriginal('status') ?: __('site.unknown'))->replace('_', ' ')->headline() }}</dd></div><div><dt class="text-stone-500">{{ __('site.bulk_delete_client') }}</dt><dd class="font-medium text-stone-900">{{ $ticket->client?->name ?? __('site.unassigned') }}</dd></div><div><dt class="text-stone-500">{{ __('site.bulk_delete_proposal') }}</dt><dd class="font-medium text-stone-900">{{ $ticket->proposal?->proposal_number ?? __('site.none') }}</dd></div></dl>
                    <div class="mt-4 grid gap-4 lg:grid-cols-3"><div><h3 class="text-sm font-semibold text-stone-900">{{ __('site.deletion_dependency_counts') }}</h3><ul class="mt-2 space-y-1 text-sm text-stone-700">@foreach ($impact->counts() as $key => $value)<li>{{ __('site.deletion_count_'.$key) }}: {{ $value }}</li>@endforeach</ul></div><div><h3 class="text-sm font-semibold text-stone-900">{{ __('site.deletion_will_delete') }}</h3><ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-stone-700">@foreach ($impact->deleteItems() as $item)<li>{{ $item }}</li>@endforeach</ul></div><div><h3 class="text-sm font-semibold text-stone-900">{{ __('site.deletion_will_preserve') }}</h3><ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-stone-700">@foreach ($impact->preserveItems() as $item)<li>{{ $item }}</li>@endforeach</ul></div></div>
                </article>
            @endforeach
        </div>
        <form method="POST" action="{{ route('admin.tickets.bulk-delete.destroy') }}" class="mt-7 border-t border-stone-200 pt-6">@csrf @method('DELETE')
            <input type="hidden" name="confirmation_token" value="{{ $confirmationToken }}">
            @if ($isAllMatching)
                <label for="typed-confirmation" class="mb-5 grid gap-2 text-sm font-semibold text-stone-900">
                    <span>{{ __('site.bulk_delete_all_matching_typed_instruction', ['phrase' => $allMatchingConfirmationPhrase]) }}</span>
                    <input id="typed-confirmation" name="typed_confirmation" type="text" autocomplete="off" class="form-input max-w-md" required>
                </label>
            @endif
            <label class="flex items-start gap-3 text-sm font-semibold text-stone-900"><input type="checkbox" name="confirm_deletion" value="1" class="mt-0.5 h-5 w-5 rounded border-stone-400 text-rose-700 focus:ring-rose-700" required><span>{{ __('site.bulk_delete_irreversible_confirmation') }}</span></label>
            <div class="mt-5 flex flex-wrap gap-3"><button type="submit" class="min-h-11 rounded-full bg-rose-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-700">{{ __('site.bulk_delete_confirm') }}</button><a href="{{ route('admin.tickets.index') }}" class="inline-flex min-h-11 items-center rounded-full border border-stone-300 bg-white px-5 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-50">{{ __('site.cancel') }}</a></div>
        </form>
    </section>
@endsection
