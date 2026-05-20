<?php

namespace App\Services\Tickets;

use App\Enums\StageEventStatus;
use App\Enums\TicketStatus;
use App\Models\Service;
use App\Models\ServiceStage;
use App\Models\Ticket;
use App\Models\TicketStageEvent;
use App\Models\User;
use App\Services\Notifications\ProjectNotificationService;
use App\Support\Tickets\TicketCodeGenerator;
use Illuminate\Support\Facades\DB;

class TicketLifecycleService
{
    public function __construct(
        private readonly TicketCodeGenerator $ticketCodeGenerator,
        private readonly ProjectNotificationService $projectNotificationService,
    ) {}

    public function createFromPublicRequest(array $payload): Ticket
    {
        return DB::transaction(function () use ($payload): Ticket {
            // Keep ticket creation and workflow initialization atomic so tracking never sees a half-built request.
            $service = Service::query()
                ->with([
                    'stages' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
                    'deliverables' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
                ])
                ->findOrFail($payload['service_id']);

            $ticket = Ticket::query()->create([
                'ticket_code' => $this->ticketCodeGenerator->generate(),
                'service_id' => $service->id,
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

            $this->syncStages($ticket, $service);
            $this->syncDeliverables($ticket, $service);

            $this->projectNotificationService->notifyTicket(
                $ticket,
                'request_received',
                __('site.email_request_received_headline'),
                __('site.email_request_received_message', ['ticket' => $ticket->ticket_code]),
            );

            return $ticket->fresh(['service', 'currentStage', 'stageEvents.serviceStage']);
        });
    }

    public function ensureDeliverables(Ticket $ticket): void
    {
        if ($ticket->deliverables()->exists()) {
            return;
        }

        $ticket->loadMissing([
            'service.deliverables' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
        ]);

        $this->syncDeliverables($ticket, $ticket->service);
    }

    public function moveToStage(Ticket $ticket, ServiceStage $targetStage, ?User $actor = null, ?string $notes = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $targetStage, $actor, $notes): Ticket {
            $orderedStages = $ticket->service
                ->stages()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

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

            $this->projectNotificationService->notifyTicket(
                $ticket,
                'stage_changed',
                __('site.email_stage_changed_headline', ['stage' => $targetStage->localizedName()]),
                $notes,
            );

            return $ticket->fresh(['service', 'currentStage', 'stageEvents.serviceStage', 'files']);
        });
    }

    public function completeStage(Ticket $ticket, TicketStageEvent $event, ?User $actor = null, ?string $notes = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $event, $actor, $notes): Ticket {
            abort_unless($event->ticket_id === $ticket->id, 404);

            $event->fill([
                'status' => StageEventStatus::COMPLETED,
                'changed_by_user_id' => $actor?->id,
                'notes' => $this->notesWithUpdate($event->notes, $notes, $actor),
                'entered_at' => $event->entered_at ?? now(),
                'completed_at' => $event->completed_at ?? now(),
            ])->save();

            $activeStageIds = $ticket->service
                ->stages()
                ->where('is_active', true)
                ->pluck('id');

            $completedCount = $ticket->stageEvents()
                ->whereIn('service_stage_id', $activeStageIds)
                ->where('status', StageEventStatus::COMPLETED)
                ->count();

            $ticket->forceFill([
                'status' => $activeStageIds->isNotEmpty() && $completedCount === $activeStageIds->count()
                    ? TicketStatus::COMPLETED
                    : TicketStatus::IN_PROGRESS,
            ])->save();

            $this->projectNotificationService->notifyTicket(
                $ticket,
                'stage_completed',
                __('site.email_stage_completed_headline', ['stage' => $event->serviceStage->localizedName()]),
                $notes,
            );

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
}
