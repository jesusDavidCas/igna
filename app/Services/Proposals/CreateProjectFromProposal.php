<?php

namespace App\Services\Proposals;

use App\Enums\TicketStatus;
use App\Models\Proposal;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Notifications\ProjectNotificationService;
use App\Services\Tickets\TicketLifecycleService;
use App\Support\Tickets\TicketCodeGenerator;
use Illuminate\Support\Facades\DB;

class CreateProjectFromProposal
{
    public function __construct(
        private readonly TicketCodeGenerator $ticketCodeGenerator,
        private readonly TicketLifecycleService $ticketLifecycleService,
        private readonly ProjectNotificationService $projectNotificationService,
    ) {}

    public function create(Proposal $proposal, Service $service, User $actor): Ticket
    {
        return DB::transaction(function () use ($proposal, $service, $actor): Ticket {
            $proposal = Proposal::query()
                ->whereKey($proposal->id)
                ->lockForUpdate()
                ->with(['client', 'project'])
                ->firstOrFail();

            if ($proposal->project) {
                return $proposal->project->fresh(['service', 'currentStage', 'stageEvents.serviceStage', 'deliverables']);
            }

            abort_unless($proposal->isProjectConvertible(), 422, __('site.proposal_project_not_eligible'));

            $service = Service::query()
                ->with([
                    'stages' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
                    'deliverables' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
                ])
                ->where('is_active', true)
                ->whereKey($service->id)
                ->firstOrFail();

            [$firstName, $lastName] = $this->clientNames($proposal);

            $ticket = Ticket::query()->create([
                'ticket_code' => $this->ticketCodeGenerator->generate(),
                'proposal_id' => $proposal->id,
                'service_id' => $service->id,
                'service_selection' => 'catalog',
                'service_public_category' => $service->publicCategoryCode(),
                'client_user_id' => $proposal->client_user_id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $proposal->clientDisplayEmail(),
                'phone' => $proposal->clientDisplayPhone(),
                'project_name' => $proposal->title_en ?: ($proposal->title ?: $proposal->localizedTitle()),
                'project_location' => $proposal->project_location,
                'preferred_language' => $proposal->client?->preferred_language ?: app()->getLocale(),
                'project_description' => $proposal->subject ?: $proposal->localizedTitle(),
                'target_date' => $proposal->requested_deadline,
                'status' => TicketStatus::NEW,
                'submitted_at' => now(),
            ]);

            $ticket = $this->ticketLifecycleService->initializeCatalogWorkflow($ticket, $service);

            $currentEvent = $ticket->stageEvents
                ->first(fn ($event): bool => $event->service_stage_id === $ticket->current_service_stage_id);

            if ($currentEvent) {
                $currentEvent->forceFill([
                    'changed_by_user_id' => $actor->id,
                    'notes' => __('site.project_created_from_proposal_note', [
                        'proposal' => $proposal->proposal_number,
                    ]),
                ])->save();
            }

            $proposal->forceFill([
                'converted_to_project_at' => now(),
                'converted_by_user_id' => $actor->id,
            ])->save();

            DB::afterCommit(function () use ($ticket): void {
                $this->projectNotificationService->notifyTicket(
                    $ticket,
                    'project_created',
                    'site.email_project_created_headline',
                    messageKey: 'site.email_project_created_message',
                    messageReplacements: ['ticket' => $ticket->ticket_code],
                );
            });

            return $ticket->fresh(['proposal', 'service', 'currentStage', 'stageEvents.serviceStage', 'deliverables']);
        });
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function clientNames(Proposal $proposal): array
    {
        if ($proposal->client) {
            return [$proposal->client->first_name, $proposal->client->last_name];
        }

        $parts = preg_split('/\s+/u', trim($proposal->prospect_name ?: ''), 2) ?: [];

        return [
            $parts[0] ?? __('site.client'),
            $parts[1] ?? '',
        ];
    }
}
