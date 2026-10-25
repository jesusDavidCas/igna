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
    public function hasRelatedInformation(Ticket $ticket): bool
    {
        $ticket->loadCount(['files', 'deliverables', 'stageEvents', 'stageAudits']);

        return $ticket->proposal_id !== null
            || $ticket->client_user_id !== null
            || $ticket->files_count > 0
            || $ticket->deliverables_count > 0
            || $ticket->stage_events_count > 0
            || $ticket->stage_audits_count > 0;
    }

    public function fingerprint(Ticket $ticket): string
    {
        $ticket->loadCount(['files', 'deliverables', 'stageEvents', 'stageAudits']);

        return hash('sha256', json_encode([
            'id' => $ticket->id,
            'updated_at' => $ticket->updated_at?->format('U.u'),
            'ticket_code' => $ticket->ticket_code,
            'project_name' => $ticket->project_name,
            'email' => $ticket->email,
            'proposal_id' => $ticket->proposal_id,
            'client_user_id' => $ticket->client_user_id,
            'current_service_stage_id' => $ticket->current_service_stage_id,
            'status' => $ticket->getRawOriginal('status'),
            'files' => $ticket->files_count,
            'deliverables' => $ticket->deliverables_count,
            'stage_events' => $ticket->stage_events_count,
            'stage_audits' => $ticket->stage_audits_count,
            'dependencies' => $this->dependencyFingerprint($ticket->id),
        ], JSON_THROW_ON_ERROR));
    }

    private function dependencyFingerprint(int $ticketId): string
    {
        $dependencies = DB::query()
            ->fromSub($this->dependencyRowsQuery([$ticketId]), 'ticket_dependencies')
            ->orderBy('relation_type')
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();

        return hash('sha256', json_encode($dependencies, JSON_THROW_ON_ERROR));
    }

    /**
     * Selects material fields from every dependent row in one query. The digest
     * complements counts so an in-place dependency edit cannot reuse a preview.
     */
    private function dependencyRowsQuery(array $ticketIds)
    {
        $files = DB::table('ticket_files')
            ->whereIn('ticket_id', $ticketIds)
            ->selectRaw("'file' AS relation_type, id, stored_name AS value_one, mime_type AS value_two, size_bytes AS value_three, storage_provider AS value_four, storage_disk AS value_five, storage_path AS value_six, google_drive_file_id AS value_seven, google_drive_url AS value_eight, ticket_deliverable_id AS value_nine, visibility AS value_ten, delivery_type AS value_eleven, upload_source AS value_twelve, review_status AS value_thirteen, is_client_visible AS value_fourteen, watermark_status AS value_fifteen, submitted_context_hash AS value_sixteen, title AS value_seventeen, original_name AS value_eighteen, deliverable_type AS value_nineteen, uploaded_by_user_id AS value_twenty, reviewed_by_user_id AS value_twenty_one, rejected_by_user_id AS value_twenty_two, rejection_reason AS value_twenty_three, first_admin_downloaded_by_user_id AS value_twenty_four, first_admin_downloaded_at AS value_twenty_five, reviewed_at AS value_twenty_six, rejected_at AS value_twenty_seven, updated_at");
        $deliverables = DB::table('ticket_deliverables')
            ->whereIn('ticket_id', $ticketIds)
            ->selectRaw("'deliverable' AS relation_type, id, service_deliverable_id AS value_one, name AS value_two, description AS value_three, status AS value_four, sort_order AS value_five, NULL AS value_six, NULL AS value_seven, NULL AS value_eight, NULL AS value_nine, NULL AS value_ten, NULL AS value_eleven, NULL AS value_twelve, NULL AS value_thirteen, NULL AS value_fourteen, NULL AS value_fifteen, NULL AS value_sixteen, NULL AS value_seventeen, NULL AS value_eighteen, NULL AS value_nineteen, NULL AS value_twenty, NULL AS value_twenty_one, NULL AS value_twenty_two, NULL AS value_twenty_three, NULL AS value_twenty_four, NULL AS value_twenty_five, NULL AS value_twenty_six, NULL AS value_twenty_seven, updated_at");
        $events = DB::table('ticket_stage_events')
            ->whereIn('ticket_id', $ticketIds)
            ->selectRaw("'stage_event' AS relation_type, id, service_stage_id AS value_one, changed_by_user_id AS value_two, status AS value_three, is_client_visible AS value_four, notes AS value_five, attempt_number AS value_six, entered_at AS value_seven, completed_at AS value_eight, superseded_at AS value_nine, superseded_by_user_id AS value_ten, superseded_reason AS value_eleven, NULL AS value_twelve, NULL AS value_thirteen, NULL AS value_fourteen, NULL AS value_fifteen, NULL AS value_sixteen, NULL AS value_seventeen, NULL AS value_eighteen, NULL AS value_nineteen, NULL AS value_twenty, NULL AS value_twenty_one, NULL AS value_twenty_two, NULL AS value_twenty_three, NULL AS value_twenty_four, NULL AS value_twenty_five, NULL AS value_twenty_six, NULL AS value_twenty_seven, updated_at");
        $audits = DB::table('ticket_stage_audits')
            ->whereIn('ticket_id', $ticketIds)
            ->selectRaw("'stage_audit' AS relation_type, id, ticket_stage_event_id AS value_one, service_stage_id AS value_two, actor_user_id AS value_three, action AS value_four, status_before AS value_five, status_after AS value_six, attempt_number AS value_seven, entered_at_snapshot AS value_eight, completed_at_snapshot AS value_nine, notes_snapshot AS value_ten, rollback_from_stage_id AS value_eleven, rollback_to_stage_id AS value_twelve, reason AS value_thirteen, NULL AS value_fourteen, NULL AS value_fifteen, NULL AS value_sixteen, NULL AS value_seventeen, NULL AS value_eighteen, NULL AS value_nineteen, NULL AS value_twenty, NULL AS value_twenty_one, NULL AS value_twenty_two, NULL AS value_twenty_three, NULL AS value_twenty_four, NULL AS value_twenty_five, NULL AS value_twenty_six, NULL AS value_twenty_seven, updated_at");

        return $files
            ->unionAll($deliverables)
            ->unionAll($events)
            ->unionAll($audits);
    }

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

    public function delete(Ticket $ticket, User $actor, ?string $expectedFingerprint = null): bool
    {
        $files = [];
        $deleted = false;

        DB::transaction(function () use ($ticket, $actor, $expectedFingerprint, &$files, &$deleted): void {
            $locked = Ticket::query()
                ->with(['files', 'proposal'])
                ->withCount(['files', 'deliverables', 'stageEvents', 'stageAudits'])
                ->whereKey($ticket->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return;
            }

            if ($expectedFingerprint !== null && ! hash_equals($expectedFingerprint, $this->fingerprint($locked))) {
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
