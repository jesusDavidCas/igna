<?php

namespace Tests\Feature;

use App\Enums\StageEventStatus;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Mail\ProjectUpdateMail;
use App\Models\Proposal;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProposalToProjectBridgeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;
    private Service $technologyService;
    private Service $engineeringService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        app()->setLocale('en');
        Mail::fake();
        $this->seed();

        $this->admin = User::query()->where('role', UserRole::SUPER_ADMIN)->firstOrFail();
        $this->client = User::factory()->create([
            'first_name' => 'Local',
            'last_name' => 'Client',
            'email' => 'phase5b1.client@example.test',
            'phone' => '+57 300 111 2222',
            'preferred_language' => 'en',
            'role' => UserRole::CLIENT,
        ]);
        $this->technologyService = $this->service('digital', 'Technology Bridge Service');
        $this->engineeringService = $this->service('engineering', 'Engineering Bridge Service');
    }

    public function test_admin_project_terminology_renders_without_visible_ticket_module_language(): void
    {
        $ticket = $this->ticket();

        $this->actingAs($this->admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.tickets.index'))
            ->assertOk()
            ->assertSee('Projects')
            ->assertSee('Project code')
            ->assertDontSee('Tickets')
            ->assertDontSee('Ticket code');

        $this->actingAs($this->admin)
            ->withSession(['locale' => 'es'])
            ->get(route('admin.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Código del proyecto')
            ->assertSee('Etapa actual')
            ->assertDontSee('Solicitud')
            ->assertDontSee('Ticket');

        $proposal = $this->proposal(['status' => 'approved']);

        $this->actingAs($this->admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.proposals.show', $proposal))
            ->assertOk()
            ->assertSee('Proposal number')
            ->assertSee('Create project')
            ->assertDontSee('Create ticket');
    }

    public function test_only_approved_unconverted_unexpired_proposals_show_create_project_action(): void
    {
        $approved = $this->proposal(['status' => 'approved']);
        $draft = $this->proposal(['status' => 'draft']);
        $rejected = $this->proposal(['status' => 'rejected']);
        $expired = $this->proposal([
            'status' => 'approved',
            'valid_until' => now()->subDay()->toDateString(),
        ]);
        $converted = $this->proposal(['status' => 'approved']);
        $project = $this->ticket(['proposal_id' => $converted->id]);

        $this->actingAs($this->admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.proposals.show', $approved))
            ->assertOk()
            ->assertSee('Create project');

        foreach ([$draft, $rejected, $expired] as $proposal) {
            $this->actingAs($this->admin)
                ->withSession(['locale' => 'en'])
                ->get(route('admin.proposals.show', $proposal))
                ->assertOk()
                ->assertDontSee('Create project');
        }

        $this->actingAs($this->admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.proposals.show', $converted))
            ->assertOk()
            ->assertSee('Open project')
            ->assertSee($project->ticket_code);
    }

    public function test_approved_proposal_creates_one_project_with_existing_workflow_and_relationships(): void
    {
        $proposal = $this->proposal([
            'status' => 'approved',
            'project_location' => 'Bogota',
            'requested_deadline' => now()->addMonth()->toDateString(),
        ]);

        $itemsBefore = $proposal->items()->get()->toArray();
        $totalBefore = (string) $proposal->total;

        $this->actingAs($this->admin)
            ->withSession(['locale' => 'en'])
            ->post(route('admin.proposals.projects.store', $proposal), [
                'service_category' => 'technology',
                'service_id' => $this->technologyService->id,
                'project_location' => 'Manipulated hidden location',
                'requested_deadline' => now()->addYears(3)->toDateString(),
            ])
            ->assertRedirect();

        $project = Ticket::query()->where('proposal_id', $proposal->id)->firstOrFail();

        $this->assertSame($proposal->id, $project->proposal_id);
        $this->assertSame($this->technologyService->id, $project->service_id);
        $this->assertSame('catalog', $project->service_selection);
        $this->assertSame('technology', $project->service_public_category);
        $this->assertSame($this->client->id, $project->client_user_id);
        $this->assertSame('Local', $project->first_name);
        $this->assertSame('Client', $project->last_name);
        $this->assertSame($this->client->email, $project->email);
        $this->assertSame($this->client->phone, $project->phone);
        $this->assertSame('Approved bridge proposal', $project->project_name);
        $this->assertSame('Bogota', $project->project_location);
        $this->assertSame($proposal->requested_deadline->toDateString(), $project->target_date->toDateString());
        $this->assertStringStartsWith('IGNA-', $project->ticket_code);
        $this->assertSame(TicketStatus::IN_PROGRESS, $project->status);
        $this->assertNotNull($project->current_service_stage_id);
        $this->assertSame(2, $project->stageEvents()->count());
        $this->assertSame(1, $project->deliverables()->count());
        $this->assertDatabaseHas('ticket_stage_events', [
            'ticket_id' => $project->id,
            'status' => StageEventStatus::CURRENT->value,
            'changed_by_user_id' => $this->admin->id,
        ]);
        $this->assertStringContainsString(
            'Project created from approved proposal '.$proposal->proposal_number,
            $project->stageEvents()->where('status', StageEventStatus::CURRENT)->firstOrFail()->notes,
        );

        $proposal->refresh();
        $this->assertNotNull($proposal->converted_to_project_at);
        $this->assertSame($this->admin->id, $proposal->converted_by_user_id);
        $this->assertSame('approved', $proposal->status);
        $this->assertSame($totalBefore, (string) $proposal->total);
        $this->assertSame($itemsBefore, $proposal->items()->get()->toArray());

        Mail::assertSent(ProjectUpdateMail::class, fn (ProjectUpdateMail $mail): bool => $mail->type === 'project_created'
            && str_contains($mail->render(), $project->ticket_code)
            && str_contains($mail->render(), 'Project code'));
    }

    public function test_validation_and_authorization_reject_invalid_conversion_inputs(): void
    {
        $proposal = $this->proposal(['status' => 'approved']);
        $inactive = $this->service('digital', 'Inactive Bridge Service', false);

        $this->actingAs($this->client)
            ->post(route('admin.proposals.projects.store', $proposal), [
                'service_category' => 'technology',
                'service_id' => $this->technologyService->id,
            ])
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->from(route('admin.proposals.show', $proposal))
            ->post(route('admin.proposals.projects.store', $proposal), [])
            ->assertRedirect(route('admin.proposals.show', $proposal))
            ->assertSessionHasErrors(['service_category', 'service_id']);

        $this->actingAs($this->admin)
            ->from(route('admin.proposals.show', $proposal))
            ->post(route('admin.proposals.projects.store', $proposal), [
                'service_category' => 'technology',
                'service_id' => $this->engineeringService->id,
            ])
            ->assertRedirect(route('admin.proposals.show', $proposal))
            ->assertSessionHasErrors('service_id');

        $this->actingAs($this->admin)
            ->from(route('admin.proposals.show', $proposal))
            ->post(route('admin.proposals.projects.store', $proposal), [
                'service_category' => 'technology',
                'service_id' => $inactive->id,
            ])
            ->assertRedirect(route('admin.proposals.show', $proposal))
            ->assertSessionHasErrors('service_id');

        $missingEmail = $this->proposal([
            'client_user_id' => null,
            'prospect_name' => 'Manual Client',
            'prospect_email' => null,
            'status' => 'approved',
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.proposals.show', $missingEmail))
            ->post(route('admin.proposals.projects.store', $missingEmail), [
                'service_category' => 'technology',
                'service_id' => $this->technologyService->id,
            ])
            ->assertRedirect(route('admin.proposals.show', $missingEmail))
            ->assertSessionHasErrors('proposal');
    }

    public function test_retries_and_unique_constraint_prevent_duplicate_projects(): void
    {
        $proposal = $this->proposal(['status' => 'approved']);
        $payload = [
            'service_category' => 'technology',
            'service_id' => $this->technologyService->id,
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.proposals.projects.store', $proposal), $payload)
            ->assertRedirect();

        $project = Ticket::query()->where('proposal_id', $proposal->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.proposals.projects.store', $proposal), $payload)
            ->assertRedirect(route('admin.tickets.show', $project));

        $this->assertSame(1, Ticket::query()->where('proposal_id', $proposal->id)->count());
        Mail::assertSent(ProjectUpdateMail::class, 1);

        $this->expectException(QueryException::class);

        $this->ticket(['proposal_id' => $proposal->id]);
    }

    public function test_project_links_back_to_source_proposal_and_public_tracking_still_works(): void
    {
        $proposal = $this->proposal(['status' => 'approved']);

        $this->actingAs($this->admin)
            ->post(route('admin.proposals.projects.store', $proposal), [
                'service_category' => 'technology',
                'service_id' => $this->technologyService->id,
            ])
            ->assertRedirect();

        $project = Ticket::query()->where('proposal_id', $proposal->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.tickets.show', $project))
            ->assertOk()
            ->assertSee('Source proposal')
            ->assertSee($proposal->proposal_number);

        $this->post(route('tracking.show'), [
            'ticket_code' => $project->ticket_code,
            'email' => $project->email,
        ])
            ->assertOk()
            ->assertSee($project->ticket_code)
            ->assertSee('Current stage');
    }

    private function proposal(array $overrides = []): Proposal
    {
        $proposal = Proposal::query()->create([
            'proposal_number' => 'IGNA-2026-P'.str_pad((string) (Proposal::query()->count() + 1), 4, '0', STR_PAD_LEFT),
            'client_user_id' => array_key_exists('client_user_id', $overrides) ? $overrides['client_user_id'] : $this->client->id,
            'prospect_name' => $overrides['prospect_name'] ?? null,
            'prospect_email' => array_key_exists('prospect_email', $overrides) ? $overrides['prospect_email'] : null,
            'prospect_phone' => $overrides['prospect_phone'] ?? null,
            'created_by_user_id' => $this->admin->id,
            'title' => $overrides['title'] ?? 'Approved bridge proposal',
            'title_en' => $overrides['title_en'] ?? ($overrides['title'] ?? 'Approved bridge proposal'),
            'title_es' => $overrides['title_es'] ?? 'Propuesta puente aprobada',
            'subject' => $overrides['subject'] ?? 'Bridge project subject',
            'description' => '<p>Stable proposal description.</p>',
            'scope' => '<p>Stable proposal scope.</p>',
            'timeline_months' => 1,
            'timeline_weeks' => 0,
            'timeline' => '1 month',
            'payment_plan' => 'Full payment - 100%',
            'payment_schedule' => [['label' => 'Full payment', 'percentage' => 100]],
            'status' => $overrides['status'] ?? 'approved',
            'tax_rate' => 0,
            'subtotal' => 100000,
            'tax_total' => 0,
            'total' => 100000,
            'issued_at' => now()->toDateString(),
            'valid_until' => $overrides['valid_until'] ?? now()->addDays(30)->toDateString(),
            'validity_days' => 30,
            'project_location' => $overrides['project_location'] ?? 'Medellin',
            'requested_deadline' => $overrides['requested_deadline'] ?? now()->addWeeks(4)->toDateString(),
        ]);

        $proposal->items()->create([
            'category' => 'General',
            'item_code' => 'B-01',
            'description' => 'Stable bridge item',
            'unit' => 'und',
            'quantity' => 1,
            'unit_value' => 100000,
            'subtotal' => 100000,
            'sort_order' => 1,
        ]);

        return $proposal;
    }

    private function ticket(array $overrides = []): Ticket
    {
        return Ticket::query()->create([
            'ticket_code' => 'IGNA-TEST-'.str_pad((string) (Ticket::query()->count() + 1), 4, '0', STR_PAD_LEFT),
            'proposal_id' => $overrides['proposal_id'] ?? null,
            'service_id' => $this->technologyService->id,
            'service_selection' => 'catalog',
            'service_public_category' => 'technology',
            'client_user_id' => $this->client->id,
            'first_name' => 'Local',
            'last_name' => 'Client',
            'email' => $this->client->email,
            'phone' => $this->client->phone,
            'project_name' => 'Visible project',
            'project_location' => 'Bogota',
            'preferred_language' => 'en',
            'project_description' => 'Visible project description.',
            'target_date' => now()->addMonth()->toDateString(),
            'status' => TicketStatus::NEW,
            'submitted_at' => now(),
        ]);
    }

    private function service(string $businessLine, string $name, bool $active = true): Service
    {
        $service = Service::query()->create([
            'name' => $name,
            'name_en' => $name,
            'name_es' => $businessLine === 'digital' ? 'Servicio puente tecnologico' : 'Servicio puente de ingenieria',
            'slug' => str($name)->slug().'-'.fake()->unique()->numberBetween(1000, 9999),
            'code' => strtoupper(substr($businessLine, 0, 3)).fake()->unique()->numberBetween(100, 999),
            'business_line' => $businessLine,
            'service_type' => $businessLine === 'digital' ? 'web_platform' : 'hydrology',
            'service_scope' => 'study',
            'description' => $name.' description.',
            'description_en' => $name.' description.',
            'description_es' => 'Descripcion del '.$name.'.',
            'deliverables_schema' => [],
            'is_active' => $active,
            'sort_order' => 10,
        ]);

        $service->stages()->createMany([
            [
                'name' => 'Kickoff',
                'name_en' => 'Kickoff',
                'name_es' => 'Inicio',
                'code' => 'KICK'.fake()->unique()->numberBetween(100, 999),
                'sort_order' => 1,
                'is_active' => true,
                'is_client_visible' => true,
            ],
            [
                'name' => 'Delivery',
                'name_en' => 'Delivery',
                'name_es' => 'Entrega',
                'code' => 'DELV'.fake()->unique()->numberBetween(100, 999),
                'sort_order' => 2,
                'is_active' => true,
                'is_client_visible' => true,
            ],
        ]);

        $service->deliverables()->create([
            'name' => 'Project package',
            'name_en' => 'Project package',
            'name_es' => 'Paquete del proyecto',
            'description' => 'Project deliverable package.',
            'description_en' => 'Project deliverable package.',
            'description_es' => 'Paquete entregable del proyecto.',
            'sort_order' => 1,
            'is_active' => true,
            'is_client_visible_by_default' => true,
        ]);

        return $service;
    }
}
