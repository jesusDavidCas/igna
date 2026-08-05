<?php

namespace App\Services\Deletion;

use App\Models\DeletionAudit;
use App\Models\Proposal;
use App\Models\User;
use App\Support\Deletion\DeletionImpact;
use Illuminate\Support\Facades\DB;

class DeleteProposal
{
    public function impact(Proposal $proposal): DeletionImpact
    {
        $proposal->loadCount(['items', 'project']);

        return new DeletionImpact(
            counts: [
                'proposal_items' => (int) $proposal->items_count,
                'linked_projects' => (int) $proposal->project_count,
            ],
            deleteItems: [
                __('site.delete_proposal_deletes_proposal'),
                __('site.delete_proposal_deletes_items'),
                __('site.delete_proposal_deletes_public_access'),
            ],
            preserveItems: [
                __('site.delete_proposal_preserves_templates'),
                __('site.delete_proposal_preserves_services'),
                __('site.delete_proposal_preserves_users'),
            ],
            blockingKeys: ['linked_projects'],
        );
    }

    public function delete(Proposal $proposal, User $actor): bool
    {
        return DB::transaction(function () use ($proposal, $actor): bool {
            $locked = Proposal::query()
                ->with('project')
                ->whereKey($proposal->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return false;
            }

            $impact = $this->impact($locked);

            if (! $impact->canDelete()) {
                throw new DeletionBlockedException(__('site.proposal_delete_linked_project_blocked', [
                    'project_code' => $locked->project?->ticket_code ?: __('site.unassigned'),
                ]));
            }

            DeletionAudit::query()->create([
                'actor_user_id' => $actor->id,
                'actor_email_snapshot' => $actor->email,
                'entity_type' => 'proposal',
                'entity_public_identifier' => $locked->proposal_number,
                'entity_label' => $locked->localizedTitle(),
                'dependency_summary' => $impact->toArray(),
                'created_at' => now(),
            ]);

            $locked->delete();

            return true;
        });
    }
}
