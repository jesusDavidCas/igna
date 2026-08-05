<?php

namespace Tests\Feature;

use App\Enums\StageEventStatus;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\DeletionAudit;
use App\Models\Proposal;
use App\Models\ProposalServiceTemplate;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Deletion\DeleteUser;
use App\Services\Deletion\DeletionBlockedException;
use App\Services\Launch\LaunchDataResetter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuardedAdministrativeDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed();

        $this->superAdmin = User::query()->where('role', UserRole::SUPER_ADMIN)->firstOrFail();
    }

    public function test_delete_routes_are_superadministrator_only(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $ticket = $this->ticket(['ticket_code' => 'PRJ-DELETE-AUTH']);

        $this->actingAs($admin)
            ->delete(route('admin.tickets.destroy', $ticket))
            ->assertForbidden();

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.tickets.destroy', $ticket))
            ->assertRedirect(route('admin.tickets.index'));

        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
        $this->assertDatabaseHas('deletion_audits', [
            'entity_type' => 'project',
            'entity_public_identifier' => 'PRJ-DELETE-AUTH',
        ]);
    }

    public function test_full_migration_set_has_project_bridge_and_deletion_audit_schema(): void
    {
        $this->assertTrue(Schema::hasColumn('tickets', 'proposal_id'));
        $this->assertTrue(Schema::hasTable('deletion_audits'));
    }

    public function test_compact_delete_controls_render_without_typed_confirmation_or_dependency_matrix(): void
    {
        $ticket = $this->ticket(['ticket_code' => 'PRJ-COMPACT']);
        $proposal = $this->proposal(['proposal_number' => 'PROP-COMPACT']);
        $user = User::factory()->create(['role' => UserRole::CLIENT]);
        $teamMember = TeamMember::query()->firstOrFail();
        $service = $this->service('COMPACT-SVC');

        $responses = [
            $this->actingAs($this->superAdmin)->get(route('admin.tickets.show', $ticket)),
            $this->actingAs($this->superAdmin)->get(route('admin.proposals.show', $proposal)),
            $this->actingAs($this->superAdmin)->get(route('admin.users.edit', $user)),
            $this->actingAs($this->superAdmin)->get(route('admin.team.edit', $teamMember)),
            $this->actingAs($this->superAdmin)->get(route('admin.services.edit', $service)),
        ];

        foreach ($responses as $response) {
            $response
                ->assertOk()
                ->assertSee(__('site.deletion_submit'))
                ->assertSee('data-delete-modal-trigger', false)
                ->assertSee('data-delete-modal', false)
                ->assertDontSee(__('site.deletion_dependency_counts'))
                ->assertDontSee(__('site.deletion_will_delete'))
                ->assertDontSee(__('site.deletion_will_preserve'))
                ->assertDontSee(__('site.deletion_confirmation_label', ['value' => 'anything']))
                ->assertDontSee('name="confirmation"', false)
                ->assertDontSee('data-typed-delete-input', false);
        }

        $responses[0]->assertSee(__('site.deletion_compact_title_project'));
        $responses[1]->assertSee(__('site.deletion_compact_title_proposal'));
        $responses[2]->assertSee(__('site.deletion_compact_title_user'));
        $responses[3]->assertSee(__('site.deletion_compact_title_team_member'));
        $responses[4]
            ->assertSee(__('site.deletion_compact_title_service'))
            ->assertSee(__('site.save_changes'));
    }

    public function test_linked_proposal_detail_loads_and_blocks_delete_with_compact_ui(): void
    {
        $proposal = $this->proposal(['proposal_number' => 'PROP-LINKED-UI']);
        $ticket = $this->ticket([
            'ticket_code' => 'PRJ-LINKED-UI',
            'proposal_id' => $proposal->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.proposals.show', $proposal))
            ->assertOk()
            ->assertSee(__('site.deletion_compact_title_proposal'))
            ->assertSee(__('site.proposal_delete_linked_project_blocked', ['project_code' => $ticket->ticket_code]))
            ->assertDontSee('name="confirmation"', false);

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.proposals.destroy', $proposal))
            ->assertSessionHasErrors('deletion');
    }

    public function test_project_delete_removes_project_history_and_files_but_preserves_linked_proposal(): void
    {
        Storage::fake('local');

        $proposal = $this->proposal([
            'proposal_number' => 'PROP-PRESERVE-1',
            'status' => 'approved',
            'converted_to_project_at' => now(),
            'converted_by_user_id' => $this->superAdmin->id,
        ]);
        $ticket = $this->ticket([
            'ticket_code' => 'PRJ-DELETE-1',
            'proposal_id' => $proposal->id,
        ]);
        $stageEvent = $ticket->stageEvents()->create([
            'service_stage_id' => $ticket->current_service_stage_id,
            'changed_by_user_id' => $this->superAdmin->id,
            'status' => StageEventStatus::CURRENT,
            'is_client_visible' => true,
            'attempt_number' => 1,
            'entered_at' => now(),
        ]);
        $ticket->stageAudits()->create([
            'ticket_stage_event_id' => $stageEvent->id,
            'service_stage_id' => $ticket->current_service_stage_id,
            'actor_user_id' => $this->superAdmin->id,
            'action' => 'entered',
            'status_after' => StageEventStatus::CURRENT,
            'attempt_number' => 1,
        ]);
        $deliverable = $ticket->deliverables()->create([
            'name' => 'Private deliverable',
            'status' => 'pending',
            'sort_order' => 1,
        ]);
        Storage::disk('local')->put('ticket-files/delete-me.pdf', '%PDF private project file');
        Storage::disk('local')->put('ticket-files/shared.pdf', '%PDF shared project file');
        $ticket->files()->create([
            'ticket_deliverable_id' => $deliverable->id,
            'title' => 'Delete me',
            'original_name' => 'delete-me.pdf',
            'storage_provider' => 'local',
            'storage_disk' => 'local',
            'storage_path' => 'ticket-files/delete-me.pdf',
            'visibility' => 'internal',
            'delivery_type' => 'internal',
            'upload_source' => 'admin',
            'review_status' => 'reviewed',
            'is_client_visible' => false,
            'uploaded_at' => now(),
        ]);
        $ticket->files()->create([
            'ticket_deliverable_id' => $deliverable->id,
            'title' => 'Shared',
            'original_name' => 'shared.pdf',
            'storage_provider' => 'local',
            'storage_disk' => 'local',
            'storage_path' => 'ticket-files/shared.pdf',
            'visibility' => 'internal',
            'delivery_type' => 'internal',
            'upload_source' => 'admin',
            'review_status' => 'reviewed',
            'is_client_visible' => false,
            'uploaded_at' => now(),
        ]);
        $otherTicket = $this->ticket(['ticket_code' => 'PRJ-SHARED-FILE']);
        $otherTicket->files()->create([
            'title' => 'Shared survivor',
            'original_name' => 'shared.pdf',
            'storage_provider' => 'local',
            'storage_disk' => 'local',
            'storage_path' => 'ticket-files/shared.pdf',
            'visibility' => 'internal',
            'delivery_type' => 'internal',
            'upload_source' => 'admin',
            'review_status' => 'reviewed',
            'is_client_visible' => false,
            'uploaded_at' => now(),
        ]);

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.tickets.destroy', $ticket))
            ->assertRedirect(route('admin.tickets.index'));

        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
        $this->assertDatabaseMissing('ticket_files', ['ticket_id' => $ticket->id]);
        $this->assertDatabaseMissing('ticket_stage_events', ['ticket_id' => $ticket->id]);
        $this->assertDatabaseMissing('ticket_stage_audits', ['ticket_id' => $ticket->id]);
        $this->assertDatabaseHas('ticket_files', ['ticket_id' => $otherTicket->id, 'storage_path' => 'ticket-files/shared.pdf']);
        Storage::disk('local')->assertMissing('ticket-files/delete-me.pdf');
        Storage::disk('local')->assertExists('ticket-files/shared.pdf');

        $proposal->refresh();
        $this->assertNull($proposal->converted_to_project_at);
        $this->assertNull($proposal->converted_by_user_id);
        $this->assertDatabaseHas('proposals', ['id' => $proposal->id, 'proposal_number' => 'PROP-PRESERVE-1']);
        $this->assertDatabaseHas('deletion_audits', [
            'entity_type' => 'project',
            'entity_public_identifier' => 'PRJ-DELETE-1',
        ]);
    }

    public function test_proposal_delete_blocks_linked_projects_and_deletes_only_unlinked_proposals(): void
    {
        $linkedProposal = $this->proposal(['proposal_number' => 'PROP-LINKED']);
        $ticket = $this->ticket([
            'ticket_code' => 'PRJ-LINKED',
            'proposal_id' => $linkedProposal->id,
        ]);
        $template = ProposalServiceTemplate::query()->firstOrFail();

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.proposals.destroy', $linkedProposal))
            ->assertSessionHasErrors('deletion');

        $this->assertDatabaseHas('proposals', ['id' => $linkedProposal->id]);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);

        $unlinkedProposal = $this->proposal(['proposal_number' => 'PROP-DELETE']);
        $unlinkedProposal->items()->create([
            'description' => 'Saved historical row',
            'quantity' => 2,
            'unit_value' => 100,
            'subtotal' => 200,
            'sort_order' => 1,
        ]);

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.proposals.destroy', $unlinkedProposal))
            ->assertRedirect(route('admin.proposals.index'));

        $this->assertDatabaseMissing('proposals', ['id' => $unlinkedProposal->id]);
        $this->assertDatabaseMissing('proposal_items', ['proposal_id' => $unlinkedProposal->id]);
        $this->assertDatabaseHas('proposal_service_templates', ['id' => $template->id]);
    }

    public function test_user_delete_blocks_current_last_and_launch_superadministrator(): void
    {
        Storage::fake('public');

        $target = User::factory()->create([
            'email' => 'delete-target@example.com',
            'role' => UserRole::CLIENT,
            'signature_path' => 'signatures/delete-target.png',
        ]);
        Storage::disk('public')->put('signatures/delete-target.png', 'signature');
        DB::table('sessions')->insert([
            'id' => 'delete-target-session',
            'user_id' => $target->id,
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);
        DB::table('password_reset_tokens')->insert([
            'email' => $target->email,
            'token' => 'hashed-token',
            'created_at' => now(),
        ]);
        $this->ticket(['client_user_id' => $target->id]);
        $this->proposal(['client_user_id' => $target->id, 'signer_user_id' => $target->id]);

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.users.destroy', $this->superAdmin))
            ->assertSessionHasErrors('deletion');

        $launch = User::factory()->create([
            'email' => LaunchDataResetter::PRESERVED_SUPERADMIN_EMAIL,
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.users.destroy', $launch))
            ->assertSessionHasErrors('deletion');

        $launch->update(['role' => UserRole::ADMIN]);
        $onlySuperAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);
        $this->superAdmin->update(['role' => UserRole::ADMIN]);

        $this->expectException(DeletionBlockedException::class);
        app(DeleteUser::class)->delete($onlySuperAdmin, $this->superAdmin);
    }

    public function test_user_delete_clears_auth_material_and_preserves_domain_records(): void
    {
        Storage::fake('public');

        $target = User::factory()->create([
            'email' => 'delete-target@example.com',
            'role' => UserRole::CLIENT,
            'signature_path' => 'signatures/delete-target.png',
        ]);
        Storage::disk('public')->put('signatures/delete-target.png', 'signature');
        DB::table('sessions')->insert([
            'id' => 'delete-target-session',
            'user_id' => $target->id,
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);
        DB::table('password_reset_tokens')->insert([
            'email' => $target->email,
            'token' => 'hashed-token',
            'created_at' => now(),
        ]);
        $ticket = $this->ticket(['client_user_id' => $target->id]);
        $proposal = $this->proposal(['client_user_id' => $target->id, 'signer_user_id' => $target->id]);
        $this->superAdmin->update(['role' => UserRole::SUPER_ADMIN]);

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $target->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $target->email]);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'client_user_id' => null]);
        $this->assertDatabaseHas('proposals', ['id' => $proposal->id, 'client_user_id' => null, 'signer_user_id' => null]);
        $this->assertDatabaseHas('deletion_audits', [
            'entity_type' => 'user',
            'entity_public_identifier' => 'delete-target@example.com',
        ]);
        Storage::disk('public')->assertMissing('signatures/delete-target.png');
    }

    public function test_team_member_delete_removes_credentials_views_and_files_but_preserves_users(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $teamMember = TeamMember::query()->create([
            'slug' => 'delete-team-member',
            'name' => 'Delete Team Member',
            'role' => 'Reviewer',
            'short_description' => 'Synthetic',
            'bio' => [],
            'expertise' => [],
            'photo_path' => 'team/photos/delete-team-member.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        Storage::disk('local')->put('team/credentials/delete-team-member/original.pdf', '%PDF original');
        Storage::disk('local')->put('team/credentials/delete-team-member/protected.pdf', '%PDF protected');
        Storage::disk('local')->put('team/credentials/shared/original.pdf', '%PDF shared original');
        Storage::disk('local')->put('team/credentials/shared/protected.pdf', '%PDF shared protected');
        Storage::disk('public')->put('team/photos/delete-team-member.png', 'photo');
        $credential = $teamMember->credentials()->create([
            'title' => 'Credential',
            'document_path' => 'team/credentials/delete-team-member/original.pdf',
            'protected_document_path' => 'team/credentials/delete-team-member/protected.pdf',
            'original_name' => 'credential.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
            'preview_page_count' => 1,
            'is_public' => true,
            'sort_order' => 1,
        ]);
        $sharedCredential = $teamMember->credentials()->create([
            'title' => 'Shared credential',
            'document_path' => 'team/credentials/shared/original.pdf',
            'protected_document_path' => 'team/credentials/shared/protected.pdf',
            'original_name' => 'shared-credential.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
            'preview_page_count' => 1,
            'is_public' => true,
            'sort_order' => 2,
        ]);
        $otherTeamMember = TeamMember::query()->create([
            'slug' => 'shared-credential-owner',
            'name' => 'Shared Credential Owner',
            'role' => 'Reviewer',
            'short_description' => 'Synthetic',
            'bio' => [],
            'expertise' => [],
            'photo_path' => null,
            'is_active' => true,
            'sort_order' => 2,
        ]);
        $otherCredential = $otherTeamMember->credentials()->create([
            'title' => 'Shared credential survivor',
            'document_path' => 'team/credentials/shared/original.pdf',
            'protected_document_path' => 'team/credentials/shared/protected.pdf',
            'original_name' => 'shared-credential.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
            'preview_page_count' => 1,
            'is_public' => true,
            'sort_order' => 1,
        ]);
        $credential->views()->create([
            'user_id' => $user->id,
            'viewed_at' => now(),
        ]);

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.team.destroy', $teamMember))
            ->assertRedirect(route('admin.team.index'));

        $this->assertDatabaseMissing('team_members', ['id' => $teamMember->id]);
        $this->assertDatabaseMissing('team_credentials', ['id' => $credential->id]);
        $this->assertDatabaseMissing('team_credentials', ['id' => $sharedCredential->id]);
        $this->assertDatabaseHas('team_credentials', ['id' => $otherCredential->id]);
        $this->assertDatabaseMissing('team_credential_views', ['team_credential_id' => $credential->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('deletion_audits', [
            'entity_type' => 'team_member',
            'entity_public_identifier' => 'delete-team-member',
        ]);
        Storage::disk('local')->assertMissing('team/credentials/delete-team-member/original.pdf');
        Storage::disk('local')->assertMissing('team/credentials/delete-team-member/protected.pdf');
        Storage::disk('local')->assertExists('team/credentials/shared/original.pdf');
        Storage::disk('local')->assertExists('team/credentials/shared/protected.pdf');
        Storage::disk('public')->assertMissing('team/photos/delete-team-member.png');
    }

    public function test_service_delete_blocks_historical_dependencies_and_deletes_unused_service_graph(): void
    {
        $usedService = $this->service('USED-SVC');
        $ticket = $this->ticket(['service_id' => $usedService->id]);

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.services.destroy', $usedService))
            ->assertSessionHasErrors('deletion');

        $this->assertDatabaseHas('services', ['id' => $usedService->id]);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);

        $unusedService = $this->service('FREE-SVC');
        $stage = $unusedService->stages()->create([
            'name' => 'Unused stage',
            'code' => 'unused_stage',
            'sort_order' => 1,
            'is_active' => true,
            'is_client_visible' => true,
        ]);
        $deliverable = $unusedService->deliverables()->create([
            'name' => 'Unused deliverable',
            'sort_order' => 1,
            'is_active' => true,
            'is_client_visible_by_default' => true,
        ]);

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.services.destroy', $unusedService))
            ->assertRedirect(route('admin.services.index'));

        $this->assertDatabaseMissing('services', ['id' => $unusedService->id]);
        $this->assertDatabaseMissing('service_stages', ['id' => $stage->id]);
        $this->assertDatabaseMissing('service_deliverables', ['id' => $deliverable->id]);
        $this->assertDatabaseHas('deletion_audits', [
            'entity_type' => 'service',
            'entity_public_identifier' => 'FREE-SVC',
        ]);
    }

    private function ticket(array $overrides = []): Ticket
    {
        $service = isset($overrides['service_id']) ? Service::query()->findOrFail($overrides['service_id']) : $this->service('SVC-'.Str::upper(Str::random(5)));
        $stage = $service->stages()->first() ?: $service->stages()->create([
            'name' => 'Discovery',
            'code' => 'discovery',
            'sort_order' => 1,
            'is_active' => true,
            'is_client_visible' => true,
        ]);

        return Ticket::query()->create([
            'ticket_code' => 'PRJ-'.Str::upper(Str::random(8)),
            'service_id' => $service->id,
            'client_user_id' => null,
            'current_service_stage_id' => $stage->id,
            'first_name' => 'Test',
            'last_name' => 'Client',
            'email' => 'client-'.Str::lower(Str::random(6)).'@example.com',
            'phone' => '+57 300 000 0000',
            'project_name' => 'Guarded deletion project',
            'preferred_language' => 'en',
            'project_description' => 'Synthetic project for guarded deletion tests.',
            'status' => TicketStatus::NEW,
            'submitted_at' => now(),
            ...$overrides,
        ]);
    }

    private function proposal(array $overrides = []): Proposal
    {
        return Proposal::query()->create([
            'proposal_number' => 'PROP-'.Str::upper(Str::random(8)),
            'title' => 'Guarded deletion proposal',
            'title_en' => 'Guarded deletion proposal',
            'title_es' => 'Propuesta de eliminación protegida',
            'subject' => 'Synthetic proposal',
            'status' => 'sent',
            'tax_rate' => 0,
            'subtotal' => 0,
            'tax_total' => 0,
            'total' => 0,
            'issued_at' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            ...$overrides,
        ]);
    }

    private function service(string $code): Service
    {
        return Service::query()->create([
            'name' => 'Service '.$code,
            'name_en' => 'Service '.$code,
            'name_es' => 'Servicio '.$code,
            'slug' => Str::slug($code).'-'.Str::lower(Str::random(5)),
            'code' => $code,
            'business_line' => 'digital',
            'service_type' => 'web_platform',
            'service_scope' => 'none',
            'description' => 'Synthetic service.',
            'description_en' => 'Synthetic service.',
            'description_es' => 'Servicio sintético.',
            'deliverables_schema' => [],
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }
}
