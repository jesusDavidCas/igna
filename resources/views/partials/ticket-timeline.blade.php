@php
    $events = $ticket->stageEvents
        ->sortBy(fn ($event) => $event->serviceStage->sort_order)
        ->filter(fn ($event) => empty($clientView) || $event->is_client_visible)
        ->values();
@endphp

<div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __('site.workflow_progress') }}</p>
            <h2 class="mt-2 text-xl font-semibold text-stone-950">{{ __('site.project_timeline') }}</h2>
        </div>
        <p class="text-[15px] text-stone-500">{{ __('site.current_stage') }}: <span class="font-semibold text-olive-800">{{ $ticket->currentStage?->localizedName() ?? __('site.pending_assignment') }}</span></p>
    </div>

    <div class="mt-8 space-y-0">
        @foreach ($events as $event)
            @php
                $isCompleted = $event->status === \App\Enums\StageEventStatus::COMPLETED;
                $isCurrent = $event->status === \App\Enums\StageEventStatus::CURRENT;
                $isStrong = $isCompleted || $isCurrent;
            @endphp
            <div class="relative grid gap-4 pb-8 last:pb-0 md:grid-cols-[2rem_1fr_auto]">
                @if (! $loop->last)
                    <div class="absolute left-4 top-8 h-full w-px {{ $isStrong ? 'bg-olive-300' : 'bg-stone-200' }}"></div>
                @endif

                <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full border-2 {{ $isStrong ? 'border-olive-700 bg-olive-700 text-white' : 'border-stone-300 bg-white text-stone-400' }}">
                    <span class="h-2.5 w-2.5 rounded-full {{ $isStrong ? 'bg-white' : 'bg-stone-300' }}"></span>
                </div>

                <div class="{{ $isStrong ? '' : 'opacity-60' }}">
                    <div class="rounded-2xl border {{ $isCurrent ? 'border-olive-300 bg-olive-50' : ($isCompleted ? 'border-stone-200 bg-stone-50' : 'border-stone-200 bg-white') }} p-4">
                        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="font-semibold text-stone-950">{{ $event->serviceStage->localizedName() }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.18em] {{ $isStrong ? 'text-olive-700' : 'text-stone-400' }}">{{ $event->status->label() }}</p>
                            </div>
                            <div class="grid gap-1 text-[15px] text-stone-500 md:text-right">
                                <p><span class="font-medium text-stone-700">{{ __('site.timeline_started') }}:</span> {{ optional($event->entered_at)->format('Y-m-d H:i') ?: __('site.pending_date') }}</p>
                                <p><span class="font-medium text-stone-700">{{ __('site.timeline_finished') }}:</span> {{ optional($event->completed_at)->format('Y-m-d H:i') ?: ($isCurrent ? __('site.in_progress') : __('site.pending_date')) }}</p>
                            </div>
                        </div>
                        @if ($event->notes)
                            <p class="mt-3 text-[15px] leading-6 text-stone-600">{{ $event->notes }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
