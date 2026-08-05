<?php

namespace App\Services\Launch;

use App\Enums\UserRole;
use App\Models\BlogPost;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\Ticket;
use App\Models\TicketDeliverable;
use App\Models\TicketFile;
use App\Models\TicketStageAudit;
use App\Models\TicketStageEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class LaunchDataResetter
{
    public const PRESERVED_SUPERADMIN_EMAIL = 'jesus.castaneda@ignastudio.com';
    public const CONFIRMATION = 'RESET-LAUNCH-DATA';

    public function preview(): array
    {
        $superAdmin = $this->preservedSuperAdmin();

        return [
            'preserved_superadmin_id' => $superAdmin->id,
            'projects' => Ticket::query()->count(),
            'project_files' => TicketFile::query()->count(),
            'project_deliverables' => TicketDeliverable::query()->count(),
            'stage_events' => TicketStageEvent::query()->count(),
            'stage_audits' => TicketStageAudit::query()->count(),
            'proposals' => Proposal::query()->count(),
            'proposal_items' => ProposalItem::query()->count(),
            'proposal_project_links' => Ticket::query()->whereNotNull('proposal_id')->count(),
            'non_superadmin_users' => User::query()->where('email', '!=', self::PRESERVED_SUPERADMIN_EMAIL)->count(),
            'sessions' => $this->tableExists('sessions')
                ? DB::table('sessions')->whereNotNull('user_id')->count()
                : 0,
        ];
    }

    public function reset(): array
    {
        $superAdmin = $this->preservedSuperAdmin();
        $counts = $this->preview();
        $files = TicketFile::query()
            ->whereNotNull('storage_path')
            ->get(['storage_disk', 'storage_path'])
            ->map(fn (TicketFile $file): array => [
                'disk' => $file->storage_disk ?: 'local',
                'path' => $file->storage_path,
            ])
            ->all();
        $deletedUserIds = User::query()
            ->where('email', '!=', self::PRESERVED_SUPERADMIN_EMAIL)
            ->pluck('id');

        DB::transaction(function () use ($superAdmin, $deletedUserIds): void {
            BlogPost::query()
                ->whereIn('created_by_user_id', $deletedUserIds)
                ->update(['created_by_user_id' => $superAdmin->id]);
            BlogPost::query()
                ->whereIn('updated_by_user_id', $deletedUserIds)
                ->update(['updated_by_user_id' => $superAdmin->id]);

            if ($this->tableExists('team_credential_views')) {
                DB::table('team_credential_views')
                    ->whereIn('user_id', $deletedUserIds)
                    ->update(['user_id' => null]);
            }

            if ($this->tableExists('sessions')) {
                DB::table('sessions')
                    ->whereIn('user_id', $deletedUserIds)
                    ->delete();
            }

            TicketStageAudit::query()->delete();
            TicketFile::query()->delete();
            TicketDeliverable::query()->delete();
            TicketStageEvent::query()->delete();
            Ticket::query()->delete();
            ProposalItem::query()->delete();
            Proposal::query()->delete();
            User::query()
                ->where('email', '!=', self::PRESERVED_SUPERADMIN_EMAIL)
                ->delete();
        });

        foreach ($files as $file) {
            if ($file['path']) {
                Storage::disk($file['disk'])->delete($file['path']);
            }
        }

        return $counts;
    }

    private function preservedSuperAdmin(): User
    {
        $superAdmin = User::query()
            ->where('email', self::PRESERVED_SUPERADMIN_EMAIL)
            ->first();

        if (! $superAdmin || $superAdmin->role !== UserRole::SUPER_ADMIN) {
            throw new RuntimeException('Preserved launch superadministrator is missing.');
        }

        return $superAdmin;
    }

    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
