<?php

namespace App\Services\Deletion;

use App\Models\DeletionAudit;
use App\Models\TeamCredential;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\Deletion\DeletionImpact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DeleteTeamMember
{
    public function impact(TeamMember $teamMember): DeletionImpact
    {
        $teamMember->loadCount('credentials');
        $credentialIds = $teamMember->credentials()->pluck('id');

        return new DeletionImpact(
            counts: [
                'credentials_deleted' => (int) $teamMember->credentials_count,
                'credential_views_deleted' => DB::table('team_credential_views')->whereIn('team_credential_id', $credentialIds)->count(),
                'profile_photos_deleted' => $this->photoIsExclusive($teamMember) && $teamMember->photo_path ? 1 : 0,
            ],
            deleteItems: [
                __('site.delete_team_member_deletes_profile'),
                __('site.delete_team_member_deletes_credentials'),
                __('site.delete_team_member_deletes_credential_views'),
                __('site.delete_team_member_deletes_files'),
            ],
            preserveItems: [
                __('site.delete_team_member_preserves_users'),
                __('site.delete_team_member_preserves_services'),
                __('site.delete_team_member_preserves_projects'),
                __('site.delete_team_member_preserves_proposals'),
            ],
        );
    }

    public function delete(TeamMember $teamMember, User $actor): bool
    {
        $localFiles = [];
        $publicFiles = [];
        $deleted = false;

        DB::transaction(function () use ($teamMember, $actor, &$localFiles, &$publicFiles, &$deleted): void {
            $locked = TeamMember::query()
                ->with('credentials')
                ->whereKey($teamMember->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return;
            }

            $impact = $this->impact($locked);

            $localFiles = $locked->credentials
                ->flatMap(fn (TeamCredential $credential): array => array_filter([
                    $credential->document_path,
                    $credential->protected_document_path,
                ]))
                ->values()
                ->all();

            if ($this->photoIsExclusive($locked) && $locked->photo_path) {
                $publicFiles[] = $locked->photo_path;
            }

            DeletionAudit::query()->create([
                'actor_user_id' => $actor->id,
                'actor_email_snapshot' => $actor->email,
                'entity_type' => 'team_member',
                'entity_public_identifier' => $locked->slug,
                'entity_label' => $locked->name,
                'dependency_summary' => $impact->toArray(),
                'created_at' => now(),
            ]);

            $locked->credentials()->each(function (TeamCredential $credential): void {
                $credential->views()->delete();
                $credential->delete();
            });
            $locked->delete();
            $deleted = true;
        });

        if ($deleted) {
            $this->deleteFiles('local', $localFiles);
            $this->deleteFiles('public', $publicFiles);
        }

        return $deleted;
    }

    private function photoIsExclusive(TeamMember $teamMember): bool
    {
        return $teamMember->photo_path
            && ! TeamMember::query()
                ->where('photo_path', $teamMember->photo_path)
                ->whereKeyNot($teamMember->getKey())
                ->exists();
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function deleteFiles(string $disk, array $paths): void
    {
        foreach (array_unique(array_filter($paths)) as $path) {
            if ($disk === 'local' && $this->credentialStillReferences($path)) {
                continue;
            }

            try {
                Storage::disk($disk)->delete($path);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    private function credentialStillReferences(string $path): bool
    {
        return TeamCredential::query()
            ->where('document_path', $path)
            ->orWhere('protected_document_path', $path)
            ->exists();
    }
}
