<?php

namespace App\Services\Tickets;

use App\Enums\StageEventStatus;
use App\Enums\TicketStatus;
use App\Models\Service;
use App\Models\ServiceStage;
use App\Models\Ticket;
use App\Models\TicketStageEvent;
use App\Models\User;
use App\Support\Tickets\TicketCodeGenerator;
use Illuminate\Support\Facades\DB;

class TicketLifecycleService
{
    public function __construct(
        private readonly TicketCodeGenerator $ticketCodeGenerator,
    ) {}

    public function createFromPublicRequest(array $payload): Ticket
    {
        return DB::transaction(function () use ($payload): Ticket {
            // Keep ticket creation and workflow initialization atomic so tracking never sees a half-built request.
            $service = Service::query()
                ->with(['stages' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
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

            // TODO: Dispatch request confirmation email when outbound notifications are enabled.

            return $ticket->fresh(['service', 'currentStage', 'stageEvents.serviceStage']);
        });
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

                if ($stage->sort_order < $targetStage->sort_order) {
                    $event->fill([
                        'status' => StageEventStatus::COMPLETED,
                        'changed_by_user_id' => $actor?->id,
                        'notes' => $notes ?: $event->notes,
                        'entered_at' => $event->entered_at ?? now(),
                        'completed_at' => now(),
                        'is_client_visible' => $stage->is_client_visible,
                    ])->save();

                    continue;
                }

                if ($stage->is($targetStage)) {
                    $event->fill([
                        'status' => StageEventStatus::CURRENT,
                        'changed_by_user_id' => $actor?->id,
                        'notes' => $notes,
                        'entered_at' => $event->entered_at ?? now(),
                        'completed_at' => null,
                        'is_client_visible' => $stage->is_client_visible,
                    ])->save();

                    continue;
                }

                $event->fill([
                    'status' => StageEventStatus::PENDING,
                    'changed_by_user_id' => $actor?->id,
                    'completed_at' => null,
                    'is_client_visible' => $stage->is_client_visible,
                ])->save();
            }

            $isFinalStage = $orderedStages->last()?->is($targetStage) ?? false;

            $ticket->fill([
                'current_service_stage_id' => $targetStage->id,
                'status' => $isFinalStage ? TicketStatus::COMPLETED : TicketStatus::IN_PROGRESS,
            ])->save();

            // TODO: Send stage update notifications to client/admin when email delivery is configured.

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
}
