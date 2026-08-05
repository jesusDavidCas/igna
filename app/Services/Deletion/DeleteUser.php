<?php

namespace App\Services\Deletion;

use App\Enums\UserRole;
use App\Models\DeletionAudit;
use App\Models\User;
use App\Services\Launch\LaunchDataResetter;
use App\Support\Deletion\DeletionImpact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DeleteUser
{
    public function impact(User $user): DeletionImpact
    {
        $user->loadCount(['tickets', 'uploadedFiles', 'proposals', 'signedProposals']);

        return new DeletionImpact(
            counts: [
                'assigned_projects_preserved' => (int) $user->tickets_count,
                'uploaded_project_files_preserved' => (int) $user->uploaded_files_count,
                'client_proposals_preserved' => (int) $user->proposals_count,
                'signed_proposals_preserved' => (int) $user->signed_proposals_count,
                'sessions_deleted' => $this->tableExists('sessions') ? DB::table('sessions')->where('user_id', $user->id)->count() : 0,
                'password_resets_deleted' => $this->tableExists('password_reset_tokens') ? DB::table('password_reset_tokens')->where('email', $user->email)->count() : 0,
                'signature_files_deleted' => $user->signature_path ? 1 : 0,
            ],
            deleteItems: [
                __('site.delete_user_deletes_account'),
                __('site.delete_user_deletes_sessions'),
                __('site.delete_user_deletes_private_user_assets'),
            ],
            preserveItems: [
                __('site.delete_user_preserves_projects'),
                __('site.delete_user_preserves_proposals'),
                __('site.delete_user_preserves_team_members'),
                __('site.delete_user_preserves_master_data'),
            ],
        );
    }

    public function delete(User $user, User $actor): bool
    {
        if ($user->is($actor)) {
            throw new DeletionBlockedException(__('site.user_delete_current_account_blocked'));
        }

        $signaturePath = null;
        $deleted = false;

        DB::transaction(function () use ($user, $actor, &$signaturePath, &$deleted): void {
            $locked = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return;
            }

            if ($locked->email === LaunchDataResetter::PRESERVED_SUPERADMIN_EMAIL && $locked->isSuperAdmin()) {
                throw new DeletionBlockedException(__('site.user_delete_launch_superadmin_blocked'));
            }

            if ($locked->isSuperAdmin() && $locked->is_active && $this->activeSuperAdminCount(lockRows: true) <= 1) {
                throw new DeletionBlockedException(__('site.last_super_admin_guard'));
            }

            $impact = $this->impact($locked);
            $signaturePath = $locked->signature_path;

            $locked->revokeAuthenticationSessions();
            $locked = $locked->fresh();

            $this->deleteSessionRows($locked);
            $this->deletePasswordResetRows($locked);
            $this->clearDatabaseNotifications($locked);
            $this->reassignBlogPostOwnership($locked, $actor);

            DeletionAudit::query()->create([
                'actor_user_id' => $actor->id,
                'actor_email_snapshot' => $actor->email,
                'entity_type' => 'user',
                'entity_public_identifier' => $locked->email,
                'entity_label' => $locked->name,
                'dependency_summary' => $impact->toArray(),
                'created_at' => now(),
            ]);

            $locked->delete();
            $deleted = true;
        });

        if ($deleted && $signaturePath) {
            try {
                Storage::disk('public')->delete($signaturePath);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $deleted;
    }

    private function activeSuperAdminCount(bool $lockRows = false): int
    {
        $query = User::query()
            ->where('role', UserRole::SUPER_ADMIN)
            ->where('is_active', true);

        if ($lockRows) {
            return $query->lockForUpdate()->pluck('id')->count();
        }

        return $query->count();
    }

    private function deleteSessionRows(User $user): void
    {
        if ($this->tableExists('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
    }

    private function deletePasswordResetRows(User $user): void
    {
        if ($this->tableExists('password_reset_tokens')) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        }
    }

    private function clearDatabaseNotifications(User $user): void
    {
        if ($this->tableExists('notifications')) {
            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $user->id)
                ->delete();
        }
    }

    private function reassignBlogPostOwnership(User $deletedUser, User $actor): void
    {
        if (! $this->tableExists('blog_posts')) {
            return;
        }

        DB::table('blog_posts')
            ->where('created_by_user_id', $deletedUser->id)
            ->update(['created_by_user_id' => $actor->id]);
        DB::table('blog_posts')
            ->where('updated_by_user_id', $deletedUser->id)
            ->update(['updated_by_user_id' => $actor->id]);
    }

    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
