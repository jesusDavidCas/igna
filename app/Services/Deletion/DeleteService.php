<?php

namespace App\Services\Deletion;

use App\Models\DeletionAudit;
use App\Models\Service;
use App\Models\User;
use App\Support\Deletion\DeletionImpact;
use Illuminate\Support\Facades\DB;

class DeleteService
{
    public function impact(Service $service): DeletionImpact
    {
        $stageIds = $service->stages()->pluck('id');
        $deliverableIds = $service->deliverables()->pluck('id');

        return new DeletionImpact(
            counts: [
                'projects' => $service->tickets()->count(),
                'current_stage_projects' => DB::table('tickets')->whereIn('current_service_stage_id', $stageIds)->count(),
                'stage_history' => DB::table('ticket_stage_events')->whereIn('service_stage_id', $stageIds)->count(),
                'stage_audits' => DB::table('ticket_stage_audits')->whereIn('service_stage_id', $stageIds)->count(),
                'ticket_deliverables' => DB::table('ticket_deliverables')->whereIn('service_deliverable_id', $deliverableIds)->count(),
                'service_stages_deleted' => $stageIds->count(),
                'service_deliverables_deleted' => $deliverableIds->count(),
                'proposal_templates' => 0,
            ],
            deleteItems: [
                __('site.delete_service_deletes_service'),
                __('site.delete_service_deletes_stages'),
                __('site.delete_service_deletes_deliverables'),
            ],
            preserveItems: [
                __('site.delete_service_preserves_categories'),
                __('site.delete_service_preserves_templates'),
                __('site.delete_service_preserves_projects'),
                __('site.delete_service_preserves_proposals'),
            ],
            blockingKeys: [
                'projects',
                'current_stage_projects',
                'stage_history',
                'stage_audits',
                'ticket_deliverables',
            ],
        );
    }

    public function delete(Service $service, User $actor): bool
    {
        return DB::transaction(function () use ($service, $actor): bool {
            $locked = Service::query()
                ->whereKey($service->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return false;
            }

            $impact = $this->impact($locked);

            if (! $impact->canDelete()) {
                throw new DeletionBlockedException(__('site.service_delete_dependency_blocked'));
            }

            DeletionAudit::query()->create([
                'actor_user_id' => $actor->id,
                'actor_email_snapshot' => $actor->email,
                'entity_type' => 'service',
                'entity_public_identifier' => $locked->code,
                'entity_label' => $locked->name,
                'dependency_summary' => $impact->toArray(),
                'created_at' => now(),
            ]);

            $locked->delete();

            return true;
        });
    }
}
