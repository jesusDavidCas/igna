<?php

namespace App\Services\Tickets;

use App\Enums\StageEventStatus;
use App\Enums\TicketStatus;
use App\Models\Service;
use App\Models\ServiceStage;
use App\Models\Ticket;
use App\Models\TicketStageAudit;
use App\Models\TicketStageEvent;
use App\Models\User;
use App\Services\Notifications\ProjectNotificationService;
use App\Services\Services\PublicServiceTaxonomy;
use App\Support\Tickets\TicketCodeGenerator;
use Illuminate\Support\Facades\DB;

class TicketLifecycleService
{
    public function __construct(
        private readonly TicketCodeGenerator $ticketCodeGenerator,
        private readonly ProjectNotificationService $projectNotificationService,
    ) {}

    public function createFromPublicRequest(array $payload, bool $notify = true): Ticket
    {
        return DB::transaction(function () use ($payload, $notify): Ticket {
            // Keep ticket creation and workflow initialization atomic so tracking never sees a half-built request.
            $isOtherRequest = ($payload['service_id'] ?? null) === PublicServiceTaxonomy::OTHER;
            $service = $isOtherRequest
                ? null
                : Service::query()
                    ->with([
                        'stages' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
                        'deliverables' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
                    ])
                    ->where('is_active', true)
                    ->findOrFail($payload['service_id']);

            $ticket = Ticket::query()->create([
                'ticket_code' => $this->ticketCodeGenerator->generate(),
                'service_id' => $service?->id,
                'service_selection' => $isOtherRequest ? PublicServiceTaxonomy::OTHER : 'catalog',
                'service_public_category' => $isOtherRequest
                    ? PublicServiceTaxonomy::OTHER
                    : $service?->publicCategoryCode(),
                'first_name' => $payload['first_name'],
                'last_name' => $payload['last_name'],
                'email' => $payload['email'],
                'phone' => $payload['phone'] ?? null,
                'project_name' => $payload['project_name'],
                'project_location' => $payload['project_location'] ?? null,
                'preferred_language' => $payload['preferred_language'],
                'project_description' => $payload['project_description'],
                'target_date' => $payload['target_date'] ?? null,
                'status' => TicketStatus::NEW,
                'submitted_at' => now(),
            ]);

            if ($service !== null) {
                $this->syncStages($ticket, $service);
                $this->syncDeliverables($ticket, $service);
            }

            if ($notify) {
                $this->projectNotificationService->notifyTicket(
                    $ticket,
                    'request_received',
                    'site.email_request_received_headline',
                    messageKey: 'site.email_request_received_message',
                    messageReplacements: ['ticket' => $ticket->ticket_code],
                );

                $this->projectNotificationService->notifyAdminsNewTicket($ticket);
            }

            return $ticket->fresh(['service', 'currentStage', 'stageEvents.serviceStage']);
        });
    }

    public function ensureDeliverables(Ticket $ticket): void
    {
        if (! $ticket->hasCatalogService()) {
            return;
        }

        if ($ticket->deliverables()->exists()) {
            return;
        }

        $ticket->loadMissing([
            'service.deliverables' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
        ]);

        $this->syncDeliverables($ticket, $ticket->service);
    }

    public function moveToStage(Ticket $ticket, ServiceStage $targetStage, ?User $actor = null, ?string $notes = null, bool $notify = true): Ticket
    {
        return DB::transaction(function () use ($ticket, $targetStage, $actor, $notes, $notify): Ticket {
            $orderedStages = $ticket->service
                ->stages()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
            $currentIndex = $orderedStages->search(fn (ServiceStage $stage): bool => $stage->id === $ticket->current_service_stage_id);
            $targetIndex = $orderedStages->search(fn (ServiceStage $stage): bool => $stage->id === $targetStage->id);

            abort_unless($currentIndex !== false && $targetIndex !== false && $targetIndex === $currentIndex, 422);

            foreach ($orderedStages as $stage) {
                $event = TicketStageEvent::query()->firstOrCreate(
                    [
                        'ticket_id' => $ticket->id,
                        'service_stage_id' => $stage->id,
                    ],
                    [
                        'is_client_visible' => $stage->is_client_visible,
                    ],
                );

                if ($stage->is($targetStage)) {
                    $event->fill([
                        'status' => StageEventStatus::CURRENT,
                        'changed_by_user_id' => $actor?->id,
                        'notes' => $this->notesWithUpdate($event->notes, $notes, $actor),
                        'entered_at' => $event->entered_at ?? now(),
                        'completed_at' => null,
                        'is_client_visible' => $stage->is_client_visible,
                    ])->save();

                    continue;
                }

                if ($event->status === StageEventStatus::COMPLETED) {
                    $event->forceFill([
                        'is_client_visible' => $stage->is_client_visible,
                    ])->save();

                    continue;
                }

                $event->fill([
                    'status' => StageEventStatus::PENDING,
                    'changed_by_user_id' => $actor?->id,
                    'is_client_visible' => $stage->is_client_visible,
                ])->save();
            }

            $ticket->fill([
                'current_service_stage_id' => $targetStage->id,
                'status' => TicketStatus::IN_PROGRESS,
            ])->save();

            if ($notify) {
                $this->projectNotificationService->notifyTicket(
                    $ticket,
                    'stage_changed',
                    'site.email_stage_changed_headline',
                    ['stage' => $targetStage],
                    message: $notes,
                );
            }

            return $ticket->fresh(['service', 'currentStage', 'stageEvents.serviceStage', 'files']);
        });
    }

    public function completeStage(Ticket $ticket, TicketStageEvent $event, ?User $actor = null, ?string $notes = null, bool $notify = true): Ticket
    {
        return DB::transaction(function () use ($ticket, $event, $actor, $notes, $notify): Ticket {
            $ticket = Ticket::query()
                ->whereKey($ticket->id)
                ->lockForUpdate()
                ->with('service')
                ->firstOrFail();
            $event = TicketStageEvent::query()
                ->whereKey($event->id)
                ->lockForUpdate()
                ->with('serviceStage')
                ->firstOrFail();

            abort_unless($event->ticket_id === $ticket->id, 404);

            if ($event->status === StageEventStatus::COMPLETED) {
                return $ticket->fresh(['service', 'currentStage', 'stageEvents.serviceStage', 'files']);
            }

            abort_unless($event->service_stage_id === $ticket->current_service_stage_id, 422);
            abort_unless($event->status === StageEventStatus::CURRENT, 422);

            $statusBefore = $event->status->value;

            $event->fill([
                'status' => StageEventStatus::COMPLETED,
                'changed_by_user_id' => $actor?->id,
                'notes' => $this->notesWithUpdate($event->notes, $notes, $actor),
                'entered_at' => $event->entered_at ?? now(),
                'completed_at' => $event->completed_at ?? now(),
            ])->save();

            $this->recordStageAudit($ticket, $event, 'completed', $statusBefore, $event->status->value, $actor, $notes);

            $orderedStages = $ticket->service
                ->stages()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->values();
            $currentIndex = $orderedStages->search(fn (ServiceStage $stage): bool => $stage->id === $event->service_stage_id);
            $nextStage = $currentIndex === false ? null : $orderedStages->get($currentIndex + 1);

            if ($nextStage) {
                TicketStageEvent::query()->firstOrCreate(
                    [
                        'ticket_id' => $ticket->id,
                        'service_stage_id' => $nextStage->id,
                    ],
                    [
                        'is_client_visible' => $nextStage->is_client_visible,
                    ],
                )->fill([
                    'status' => StageEventStatus::CURRENT,
                    'changed_by_user_id' => $actor?->id,
                    'entered_at' => now(),
                    'completed_at' => null,
                    'is_client_visible' => $nextStage->is_client_visible,
                ])->save();
            }

            $ticket->forceFill([
                'current_service_stage_id' => $nextStage?->id ?? $event->service_stage_id,
                'status' => $nextStage ? TicketStatus::IN_PROGRESS : TicketStatus::COMPLETED,
            ])->save();

            if ($notify) {
                $this->projectNotificationService->notifyTicket(
                    $ticket,
                    'stage_completed',
                    'site.email_stage_completed_headline',
                    ['stage' => $event->serviceStage],
                    message: $notes,
                );
            }

            return $ticket->fresh(['service', 'currentStage', 'stageEvents.serviceStage', 'files']);
        });
    }

    public function reopenStage(Ticket $ticket, TicketStageEvent $event, ?User $actor = null, ?string $notes = null, bool $notify = true): Ticket
    {
        return DB::transaction(function () use ($ticket, $event, $actor, $notes, $notify): Ticket {
            $ticket = Ticket::query()
                ->whereKey($ticket->id)
                ->lockForUpdate()
                ->with('service')
                ->firstOrFail();
            $event = TicketStageEvent::query()->whereKey($event->id)->lockForUpdate()->with('serviceStage')->firstOrFail();

            abort_unless($event->ticket_id === $ticket->id, 404);

            if (
                $event->status === StageEventStatus::CURRENT
                && $ticket->current_service_stage_id === $event->service_stage_id
            ) {
                return $ticket->fresh(['service', 'currentStage', 'stageEvents.serviceStage', 'files']);
            }

            abort_unless($event->status === StageEventStatus::COMPLETED, 422);

            $orderedStages = $ticket->service
                ->stages()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->values();
            $currentIndex = $orderedStages->search(fn (ServiceStage $stage): bool => $stage->id === $ticket->current_service_stage_id);
            $targetIndex = $orderedStages->search(fn (ServiceStage $stage): bool => $stage->id === $event->service_stage_id);

            abort_unless($currentIndex !== false && $targetIndex !== false && $targetIndex === $currentIndex - 1, 422);

            $currentEvent = TicketStageEvent::query()
                ->where('ticket_id', $ticket->id)
                ->where('service_stage_id', $ticket->current_service_stage_id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($currentEvent->status === StageEventStatus::CURRENT, 422);

            $reopenNote = __('site.admin_correction_note');

            if (filled($notes)) {
                $reopenNote .= "\n".$notes;
            }

            $this->recordStageAudit(
                $ticket,
                $currentEvent,
                'rolled_back_from',
                $currentEvent->status->value,
                StageEventStatus::PENDING->value,
                $actor,
                $notes,
                $currentEvent->service_stage_id,
                $event->service_stage_id,
            );

            $currentEvent->forceFill([
                'status' => StageEventStatus::PENDING,
                'changed_by_user_id' => $actor?->id,
                'entered_at' => null,
                'completed_at' => null,
                'superseded_at' => now(),
                'superseded_by_user_id' => $actor?->id,
                'superseded_reason' => $notes,
            ])->save();

            TicketStageEvent::query()
                ->where('ticket_id', $ticket->id)
                ->whereNotIn('service_stage_id', $orderedStages->take($targetIndex + 1)->pluck('id'))
                ->update([
                    'status' => StageEventStatus::PENDING,
                    'entered_at' => null,
                    'completed_at' => null,
                    'superseded_at' => now(),
                    'superseded_by_user_id' => $actor?->id,
                    'superseded_reason' => $notes,
                ]);

            $statusBefore = $event->status->value;

            $event->fill([
                'status' => StageEventStatus::CURRENT,
                'changed_by_user_id' => $actor?->id,
                'notes' => $this->notesWithUpdate($event->notes, $reopenNote, $actor),
                'attempt_number' => $event->attempt_number + 1,
                'entered_at' => now(),
                'completed_at' => null,
                'superseded_at' => null,
                'superseded_by_user_id' => null,
                'superseded_reason' => null,
            ])->save();

            $this->recordStageAudit(
                $ticket,
                $event,
                'reopened_previous',
                $statusBefore,
                $event->status->value,
                $actor,
                $notes,
                $currentEvent->service_stage_id,
                $event->service_stage_id,
            );

            $ticket->forceFill([
                'current_service_stage_id' => $event->service_stage_id,
                'status' => TicketStatus::IN_PROGRESS,
            ])->save();

            if ($notify) {
                $this->projectNotificationService->notifyTicket(
                    $ticket,
                    'stage_reopened',
                    'site.email_stage_reopened_headline',
                    ['stage' => $event->serviceStage],
                    'site.email_stage_reopened_message',
                );
            }

            return $ticket->fresh(['service', 'currentStage', 'stageEvents.serviceStage', 'files']);
        });
    }

    private function syncStages(Ticket $ticket, Service $service): void
    {
        $stages = $service->stages;
        $firstStage = $stages->first();

        foreach ($stages as $stage) {
            TicketStageEvent::query()->create([
                'ticket_id' => $ticket->id,
                'service_stage_id' => $stage->id,
                'status' => $firstStage && $stage->is($firstStage)
                    ? StageEventStatus::CURRENT
                    : StageEventStatus::PENDING,
                'is_client_visible' => $stage->is_client_visible,
                'entered_at' => $firstStage && $stage->is($firstStage) ? now() : null,
            ]);
        }

        $ticket->forceFill([
            'current_service_stage_id' => $firstStage?->id,
            'status' => $firstStage ? TicketStatus::IN_PROGRESS : TicketStatus::NEW,
        ])->save();
    }

    private function syncDeliverables(Ticket $ticket, Service $service): void
    {
        $serviceDeliverables = $service->relationLoaded('deliverables')
            ? $service->deliverables
            : $service->deliverables()->where('is_active', true)->orderBy('sort_order')->get();

        if ($serviceDeliverables->isNotEmpty()) {
            foreach ($serviceDeliverables as $deliverable) {
                $ticket->deliverables()->firstOrCreate(
                    ['service_deliverable_id' => $deliverable->id],
                    [
                        'name' => $deliverable->name,
                        'description' => $deliverable->description,
                        'status' => 'pending',
                        'sort_order' => $deliverable->sort_order,
                    ],
                );
            }

            return;
        }

        foreach (($service->deliverables_schema ?? []) as $index => $name) {
            $ticket->deliverables()->firstOrCreate(
                ['name' => $name],
                [
                    'description' => null,
                    'status' => 'pending',
                    'sort_order' => $index + 1,
                ],
            );
        }
    }

    private function notesWithUpdate(?string $existingNotes, ?string $newNotes, ?User $actor): ?string
    {
        if (! filled($newNotes)) {
            return $existingNotes;
        }

        $entry = '['.now()->format('Y-m-d H:i').']';

        if ($actor) {
            $entry .= ' '.$actor->name.':';
        }

        $entry .= "\n".$newNotes;

        return filled($existingNotes) ? $existingNotes."\n\n".$entry : $entry;
    }

    private function recordStageAudit(
        Ticket $ticket,
        TicketStageEvent $event,
        string $action,
        ?string $statusBefore,
        ?string $statusAfter,
        ?User $actor,
        ?string $reason = null,
        ?int $rollbackFromStageId = null,
        ?int $rollbackToStageId = null,
    ): void {
        TicketStageAudit::query()->create([
            'ticket_id' => $ticket->id,
            'ticket_stage_event_id' => $event->id,
            'service_stage_id' => $event->service_stage_id,
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'attempt_number' => $event->attempt_number,
            'entered_at_snapshot' => $event->entered_at,
            'completed_at_snapshot' => $event->completed_at,
            'notes_snapshot' => $event->notes,
            'rollback_from_stage_id' => $rollbackFromStageId,
            'rollback_to_stage_id' => $rollbackToStageId,
            'reason' => $reason,
        ]);
    }
}
