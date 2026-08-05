<?php

namespace Tests\Feature;

use App\Enums\BlogPostStatus;
use App\Enums\StageEventStatus;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\BlogPost;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\ProposalServiceTemplate;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketDeliverable;
use App\Models\TicketFile;
use App\Models\TicketStageAudit;
use App\Models\TicketStageEvent;
use App\Models\User;
use App\Services\Launch\LaunchDataResetter;
use Database\Seeders\ProposalServiceTemplateSeeder;
use Database\Seeders\ServiceCatalogSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TeamMemberSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LaunchDataResetTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $client;
    private User $admin;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([
            ServiceCatalogSeeder::class,
            ProposalServiceTemplateSeeder::class,
            SettingSeeder::class,
            TeamMemberSeeder::class,
        ]);

        $this->superAdmin = User::factory()->create([
            'email' => LaunchDataResetter::PRESERVED_SUPERADMIN_EMAIL,
            'role' => UserRole::SUPER_ADMIN,
            'password' => Hash::make('PreservedLocal123!'),
        ]);
        $this->client = User::factory()->create(['role' => UserRole::CLIENT]);
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->service = Service::query()->with(['stages', 'deliverables'])->firstOrFail();
    }

    public function test_launch_reset_dry_run_reports_counts_and_deletes_nothing(): void
    {
        $this->createOperationalData();

        $this->artisan('igna:launch-reset')
            ->assertExitCode(0)
            ->expectsOutputToContain('projects')
            ->expectsOutputToContain('Dry run only');

        $this->assertSame(1, Ticket::query()->count());
        $this->assertSame(1, Proposal::query()->count());
        $this->assertSame(3, User::query()->count());
    }

    public function test_launch_reset_force_deletes_operational_data_and_preserves_master_data(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('ticket-files/reset-proof.txt', 'project file');
        $this->createOperationalData();
        Setting::query()->where('key', 'brand_favicon_path')->update(['value' => 'branding/favicon.png']);

        $serviceCount = Service::query()->count();
        $stageCount = $this->service->stages()->count();
        $deliverableCount = $this->service->deliverables()->count();
        $templateCount = ProposalServiceTemplate::query()->count();
        $templateItemCount = ProposalServiceTemplate::query()->withCount('items')->get()->sum('items_count');

        $this->artisan('igna:launch-reset', [
            '--force' => true,
            '--confirm' => LaunchDataResetter::CONFIRMATION,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Launch data reset completed');

        $this->assertSame(0, Ticket::query()->count());
        $this->assertSame(0, TicketFile::query()->count());
        $this->assertSame(0, TicketDeliverable::query()->count());
        $this->assertSame(0, TicketStageEvent::query()->count());
        $this->assertSame(0, TicketStageAudit::query()->count());
        $this->assertSame(0, Proposal::query()->count());
        $this->assertSame(0, ProposalItem::query()->count());
        $this->assertSame(1, User::query()->count());
        $this->assertDatabaseHas('users', ['email' => LaunchDataResetter::PRESERVED_SUPERADMIN_EMAIL]);
        $this->assertDatabaseHas('settings', ['key' => 'brand_favicon_path', 'value' => 'branding/favicon.png']);
        $this->assertSame($serviceCount, Service::query()->count());
        $this->assertSame($stageCount, $this->service->stages()->count());
        $this->assertSame($deliverableCount, $this->service->deliverables()->count());
        $this->assertSame($templateCount, ProposalServiceTemplate::query()->count());
        $this->assertSame($templateItemCount, ProposalServiceTemplate::query()->withCount('items')->get()->sum('items_count'));
        Storage::disk('local')->assertMissing('ticket-files/reset-proof.txt');

        $this->post(route('login.store'), [
            'email' => LaunchDataResetter::PRESERVED_SUPERADMIN_EMAIL,
            'password' => 'PreservedLocal123!',
        ])->assertRedirect(route('admin.dashboard'));

        $this->artisan('igna:launch-reset', [
            '--force' => true,
            '--confirm' => LaunchDataResetter::CONFIRMATION,
        ])->assertExitCode(0);
    }

    public function test_launch_reset_refuses_wrong_confirmation_and_missing_superadmin(): void
    {
        $this->artisan('igna:launch-reset', ['--force' => true, '--confirm' => 'WRONG'])
            ->assertExitCode(1)
            ->expectsOutputToContain('Refusing launch reset');

        $this->superAdmin->delete();

        $this->artisan('igna:launch-reset')
            ->assertExitCode(1)
            ->expectsOutputToContain('Preserved launch superadministrator is missing.');
    }

    private function createOperationalData(): void
    {
        $proposal = Proposal::query()->forceCreate([
            'proposal_number' => 'PROP-RESET-001',
            'client_user_id' => $this->client->id,
            'created_by_user_id' => $this->admin->id,
            'signer_user_id' => $this->admin->id,
            'title' => 'Reset proposal',
            'title_en' => 'Reset proposal',
            'title_es' => 'Propuesta de reinicio',
            'subject' => 'Reset',
            'description' => 'Reset proposal.',
            'scope' => 'Scope',
            'timeline_months' => 1,
            'timeline_weeks' => 0,
            'timeline' => 'One month',
            'payment_plan' => 'One payment',
            'payment_schedule' => [],
            'status' => 'approved',
            'tax_rate' => 0,
            'subtotal' => 100,
            'tax_total' => 0,
            'total' => 100,
            'issued_at' => now()->toDateString(),
            'valid_until' => now()->addMonth()->toDateString(),
            'validity_days' => 30,
            'converted_to_project_at' => now(),
            'converted_by_user_id' => $this->admin->id,
        ]);

        $proposal->items()->create([
            'category' => 'General',
            'description' => 'Reset item',
            'unit' => 'und',
            'quantity' => 1,
            'unit_value' => 100,
            'subtotal' => 100,
            'sort_order' => 1,
        ]);

        $ticket = Ticket::query()->forceCreate([
            'ticket_code' => 'IGNA-RESET-001',
            'proposal_id' => $proposal->id,
            'service_id' => $this->service->id,
            'service_selection' => 'catalog',
            'service_public_category' => 'technology',
            'client_user_id' => $this->client->id,
            'current_service_stage_id' => $this->service->stages()->firstOrFail()->id,
            'first_name' => 'Reset',
            'last_name' => 'Client',
            'email' => $this->client->email,
            'phone' => '+57 300 000 0000',
            'project_name' => 'Reset project',
            'preferred_language' => 'en',
            'project_description' => 'Reset project.',
            'status' => TicketStatus::IN_PROGRESS,
            'submitted_at' => now(),
        ]);

        $deliverable = TicketDeliverable::query()->create([
            'ticket_id' => $ticket->id,
            'service_deliverable_id' => $this->service->deliverables()->first()?->id,
            'name' => 'Reset deliverable',
            'status' => 'pending',
            'sort_order' => 1,
        ]);

        TicketFile::query()->create([
            'ticket_id' => $ticket->id,
            'uploaded_by_user_id' => $this->admin->id,
            'ticket_deliverable_id' => $deliverable->id,
            'title' => 'Reset file',
            'original_name' => 'reset-proof.txt',
            'stored_name' => 'reset-proof.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 12,
            'storage_provider' => 'local',
            'storage_disk' => 'local',
            'storage_path' => 'ticket-files/reset-proof.txt',
            'deliverable_type' => 'project_document',
            'visibility' => 'internal',
            'delivery_type' => 'internal',
            'upload_source' => 'admin',
            'review_status' => 'reviewed',
            'is_client_visible' => false,
            'uploaded_at' => now(),
        ]);

        $event = TicketStageEvent::query()->create([
            'ticket_id' => $ticket->id,
            'service_stage_id' => $this->service->stages()->firstOrFail()->id,
            'changed_by_user_id' => $this->admin->id,
            'status' => StageEventStatus::CURRENT,
            'is_client_visible' => true,
            'attempt_number' => 1,
            'entered_at' => now(),
        ]);

        TicketStageAudit::query()->create([
            'ticket_id' => $ticket->id,
            'ticket_stage_event_id' => $event->id,
            'service_stage_id' => $event->service_stage_id,
            'actor_user_id' => $this->admin->id,
            'action' => 'created',
            'status_after' => StageEventStatus::CURRENT->value,
            'attempt_number' => 1,
        ]);

        BlogPost::query()->create([
            'title' => 'Preserved post',
            'slug' => 'preserved-post',
            'summary' => 'Preserved blog content.',
            'body_html' => '<p>Preserved.</p>',
            'status' => BlogPostStatus::DRAFT,
            'created_by_user_id' => $this->admin->id,
            'updated_by_user_id' => $this->admin->id,
        ]);
    }
}
