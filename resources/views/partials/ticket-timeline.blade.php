<div class="space-y-4">
    @foreach ($ticket->stageEvents->sortBy(fn ($event) => $event->serviceStage->sort_order) as $event)
        @if (! empty($clientView) && ! $event->is_client_visible)
            @continue
        @endif

        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-semibold text-stone-900">{{ $event->serviceStage->localizedName() }}</p>
                    <p class="text-xs uppercase tracking-[0.18em] text-stone-500">{{ $event->status->label() }}</p>
                </div>
                <div class="text-sm text-stone-500">
                    @if ($event->entered_at)
                        <p>{{ __('site.timeline_entered') }} {{ $event->entered_at->format('Y-m-d H:i') }}</p>
                    @endif
                    @if ($event->completed_at)
                        <p>{{ __('site.timeline_completed') }} {{ $event->completed_at->format('Y-m-d H:i') }}</p>
                    @endif
                </div>
            </div>
            @if ($event->notes)
                <p class="mt-3 text-sm leading-6 text-stone-600">{{ $event->notes }}</p>
            @endif
        </div>
    @endforeach
</div>
