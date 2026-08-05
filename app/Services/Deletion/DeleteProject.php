<?php

namespace App\Services\Deletion;

use App\Models\DeletionAudit;
use App\Models\Proposal;
use App\Models\Ticket;
use App\Models\TicketFile;
use App\Models\User;
use App\Support\Deletion\DeletionImpact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DeleteProject
{
    public function impact(Ticket $ticket): DeletionImpact
    {
        $ticket->loadCount(['files', 'deliverables', 'stageEvents', 'stageAudits']);

        return new DeletionImpact(
            counts: [
                'project_files' => (int) $ticket->files_count,
                'project_deliverables' => (int) $ticket->deliverables_count,
                'stage_events' => (int) $ticket->stage_events_count,
                'stage_audits' => (int) $ticket->stage_audits_count,
                'linked_proposals_preserved' => $ticket->proposal_id ? 1 : 0,
            ],
            deleteItems: [
                __('site.delete_project_deletes_project'),
                __('site.delete_project_deletes_stage_history'),
                __('site.delete_project_deletes_project_files'),
                __('site.delete_project_deletes_deliverables'),
            ],
            preserveItems: [
                __('site.delete_project_preserves_service'),
                __('site.delete_project_preserves_users'),
                __('site.delete_project_preserves_linked_proposal'),
            ],
        );
    }

    public function delete(Ticket $ticket, User $actor): bool
    {
        $files = [];
        $deleted = false;

        DB::transaction(function () use ($ticket, $actor, &$files, &$deleted): void {
            $locked = Ticket::query()
                ->with(['files', 'proposal'])
                ->whereKey($ticket->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return;
            }

            $impact = $this->impact($locked);
            $files = $locked->files
                ->filter(fn (TicketFile $file): bool => filled($file->storage_path))
                ->map(fn (TicketFile $file): array => [
                    'disk' => $file->storage_disk ?: 'local',
                    'path' => $file->storage_path,
                ])
                ->values()
                ->all();

            if ($locked->proposal instanceof Proposal) {
                $locked->proposal->forceFill([
                    'converted_to_project_at' => null,
                    'converted_by_user_id' => null,
                ])->save();
            }

            DeletionAudit::query()->create([
                'actor_user_id' => $actor->id,
                'actor_email_snapshot' => $actor->email,
                'entity_type' => 'project',
                'entity_public_identifier' => $locked->ticket_code,
                'entity_label' => $locked->project_name,
                'dependency_summary' => $impact->toArray(),
                'created_at' => now(),
            ]);

            $locked->delete();
            $deleted = true;
        });

        if ($deleted) {
            $this->deleteFiles($files);
        }

        return $deleted;
    }

    /**
     * @param  array<int, array{disk:string, path:string}>  $files
     */
    private function deleteFiles(array $files): void
    {
        foreach ($files as $file) {
            if ($this->ticketFileStillReferences($file['disk'], $file['path'])) {
                continue;
            }

            try {
                Storage::disk($file['disk'])->delete($file['path']);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    private function ticketFileStillReferences(string $disk, string $path): bool
    {
        return TicketFile::query()
            ->where('storage_path', $path)
            ->where(function ($query) use ($disk): void {
                $query->where('storage_disk', $disk);

                if ($disk === 'local') {
                    $query->orWhereNull('storage_disk');
                }
            })
            ->exists();
    }
}
