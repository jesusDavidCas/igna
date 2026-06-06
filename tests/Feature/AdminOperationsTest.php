<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\ProjectUpdateMail;
use App\Models\BlogPost;
use App\Models\Proposal;
use App\Models\Service;
use App\Models\Setting;
use App\Models\TeamCredentialView;
use App\Models\TeamMember;
use App\Models\Ticket;
use App\Models\TicketFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Mail::fake();
        $this->seed();

        $this->superAdmin = User::query()->where('role', UserRole::SUPER_ADMIN)->firstOrFail();
    }

    public function test_super_admin_can_create_client_and_assign_ticket(): void
    {
        $this->actingAs($this->superAdmin);

        $this->post(route('admin.users.store'), [
            'first_name' => 'Client',
            'last_name' => 'One',
            'email' => 'client.one@example.com',
            'phone' => '+57 300 111 2222',
            'preferred_language' => 'es',
            'role' => UserRole::CLIENT->value,
            'is_active' => '1',
            'password' => 'Client12345!',
        ])->assertRedirect(route('admin.users.index'));

        $client = User::query()->where('email', 'client.one@example.com')->firstOrFail();
        $ticket = $this->createTicket();

        $this->put(route('admin.tickets.client.update', $ticket), [
            'client_user_id' => $client->id,
        ])->assertRedirect(route('admin.tickets.show', $ticket));

        $this->assertTrue($ticket->fresh()->client->is($client));

        $this->actingAs($client)
            ->get(route('client.dashboard'))
            ->assertOk()
            ->assertSee($ticket->project_name);
    }

    public function test_admin_cannot_manage_users_or_settings(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
        $client = User::factory()->create([
            'role' => UserRole::CLIENT,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('admin.users.password.update', $client), [
                'password' => 'ClientReset123!',
                'password_confirmation' => 'ClientReset123!',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertForbidden();
    }

    public function test_super_admin_can_reset_a_user_password(): void
    {
        $client = User::factory()->create([
            'email' => 'reset.client@example.com',
            'role' => UserRole::CLIENT,
        ]);

        $this->actingAs($this->superAdmin)
            ->put(route('admin.users.password.update', $client), [
                'password' => 'NewClient123!',
                'password_confirmation' => 'NewClient123!',
            ])
            ->assertRedirect(route('admin.users.edit', $client));

        $this->post(route('logout'));

        $this->post(route('login.store'), [
            'email' => 'reset.client@example.com',
            'password' => 'NewClient123!',
        ])->assertRedirect(route('client.dashboard'));
    }

    public function test_admin_can_toggle_ticket_file_visibility(): void
    {
        Storage::fake('local');

        $this->actingAs($this->superAdmin);

        $ticket = $this->createTicket();

        $this->post(route('admin.tickets.files.store', $ticket), [
            'title' => 'Hydraulic report',
            'deliverable_type' => 'report',
            'file' => UploadedFile::fake()->create('report.pdf', 128, 'application/pdf'),
        ])->assertRedirect(route('admin.tickets.show', $ticket));

        $file = TicketFile::query()
            ->where('ticket_id', $ticket->id)
            ->where('title', 'Hydraulic report')
            ->firstOrFail();
        $this->assertFalse($file->is_client_visible);

        Mail::fake();

        $this->put(route('admin.tickets.files.visibility.update', [$ticket, $file]))
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $this->assertTrue($file->fresh()->is_client_visible);

        Mail::assertSent(ProjectUpdateMail::class, fn (ProjectUpdateMail $mail): bool => $mail->type === 'file_available');
    }

    public function test_admin_selects_current_stage_without_auto_completing_and_can_complete_explicitly(): void
    {
        $this->actingAs($this->superAdmin);

        $ticket = $this->createTicket()->load(['service.stages', 'stageEvents.serviceStage']);
        $secondStage = $ticket->service->stages()->orderBy('sort_order')->skip(1)->firstOrFail();

        Mail::fake();

        $this->put(route('admin.tickets.stage.update', $ticket), [
            'service_stage_id' => $secondStage->id,
            'notes' => 'Started technical review.',
        ])->assertRedirect(route('admin.tickets.show', $ticket));

        $ticket->refresh()->load('stageEvents.serviceStage');

        $firstEvent = $ticket->stageEvents->sortBy(fn ($event) => $event->serviceStage->sort_order)->first();
        $secondEvent = $ticket->stageEvents->firstWhere('service_stage_id', $secondStage->id);

        $this->assertSame('pending', $firstEvent->status->value);
        $this->assertSame('current', $secondEvent->status->value);
        $this->assertNotNull($secondEvent->entered_at);
        $this->assertNull($secondEvent->completed_at);
        Mail::assertSent(ProjectUpdateMail::class, fn (ProjectUpdateMail $mail): bool => $mail->type === 'stage_changed');

        $this->put(route('admin.tickets.stages.complete', [$ticket, $secondEvent]), [
            'stage_event_id' => $secondEvent->id,
            'notes' => 'Review finished.',
        ])->assertRedirect(route('admin.tickets.show', $ticket));

        $this->assertSame('completed', $secondEvent->fresh()->status->value);
        $this->assertNotNull($secondEvent->fresh()->completed_at);
        Mail::assertSent(ProjectUpdateMail::class, fn (ProjectUpdateMail $mail): bool => $mail->type === 'stage_completed');
    }

    public function test_project_update_email_uses_branded_customer_template(): void
    {
        $ticket = $this->createTicket()->fresh(['currentStage', 'service', 'stageEvents.serviceStage']);

        $html = (new ProjectUpdateMail(
            ticket: $ticket,
            type: 'stage_changed',
            headline: 'Your project moved forward',
            message: 'We are reviewing your information and preparing the next step.',
        ))->render();

        $this->assertStringContainsString('IGNA Studio', $html);
        $this->assertStringContainsString('Your project moved forward', $html);
        $this->assertStringContainsString(__('site.email_project_summary'), $html);
        $this->assertStringContainsString(__('site.email_next_steps'), $html);
        $this->assertStringContainsString(__('site.email_view_tracking'), $html);
        $this->assertStringContainsString($ticket->ticket_code, $html);
    }

    public function test_reopening_a_completed_stage_sends_customer_notification(): void
    {
        $this->actingAs($this->superAdmin);

        $ticket = $this->createTicket()->load(['service.stages', 'stageEvents.serviceStage']);
        $event = $ticket->stageEvents->first();

        $this->put(route('admin.tickets.stages.complete', [$ticket, $event]), [
            'stage_event_id' => $event->id,
            'notes' => 'Completed by mistake.',
        ])->assertRedirect(route('admin.tickets.show', $ticket));

        Mail::fake();

        $this->put(route('admin.tickets.stages.reopen', [$ticket, $event]), [
            'stage_event_id' => $event->id,
            'notes' => 'Reopened for correction.',
        ])->assertRedirect(route('admin.tickets.show', $ticket));

        Mail::assertSent(ProjectUpdateMail::class, fn (ProjectUpdateMail $mail): bool => $mail->type === 'stage_reopened');
    }

    public function test_admin_can_create_classified_service_with_deliverables(): void
    {
        $this->actingAs($this->superAdmin);

        $this->post(route('admin.services.store'), [
            'name' => 'Hydraulic Diagnostic Review',
            'code' => 'HDR',
            'business_line' => 'engineering',
            'service_type' => 'hydrology',
            'service_scope' => 'study',
            'description' => 'Technical review for hydrology and hydraulic project inputs.',
            'deliverables' => "Project diagnostic report\nRainfall-runoff notes",
            'is_active' => '1',
        ])->assertRedirect(route('admin.services.index'));

        $service = Service::query()->where('code', 'HDR')->firstOrFail();

        $this->assertSame('engineering', $service->business_line);
        $this->assertSame('hydrology', $service->service_type);
        $this->assertSame('study', $service->service_scope);
        $this->assertSame(['Project diagnostic report', 'Rainfall-runoff notes'], $service->deliverables_schema);
    }

    public function test_service_type_must_match_business_line(): void
    {
        $this->actingAs($this->superAdmin);

        $this->post(route('admin.services.store'), [
            'name' => 'Invalid Mixed Service',
            'code' => 'IMS',
            'business_line' => 'digital',
            'service_type' => 'hydrology',
            'service_scope' => 'study',
            'description' => 'Invalid because hydrology belongs to engineering.',
            'is_active' => '1',
        ])->assertSessionHasErrors('service_type');
    }

    public function test_blog_html_is_sanitized_before_public_rendering(): void
    {
        $this->actingAs($this->superAdmin);

        $this->post(route('admin.blog.store'), [
            'title' => 'Security Note',
            'summary' => 'A short operational note.',
            'body_html' => '<p onclick="alert(1)">Safe paragraph</p><script>alert(1)</script><a href="javascript:alert(1)">bad link</a>',
            'status' => 'published',
            'published_at' => null,
            'seo_keywords' => 'security, blog',
        ])->assertRedirect(route('admin.blog.index'));

        $post = BlogPost::query()->where('slug', 'security-note')->firstOrFail();

        $this->assertStringNotContainsString('<script', $post->body_html);
        $this->assertStringNotContainsString('onclick', $post->body_html);
        $this->assertStringNotContainsString('javascript:', $post->body_html);
        $this->assertNotNull($post->published_at);
    }

    public function test_last_active_super_admin_cannot_be_removed(): void
    {
        $this->actingAs($this->superAdmin);

        $this->put(route('admin.users.update', $this->superAdmin), [
            'first_name' => $this->superAdmin->first_name,
            'last_name' => $this->superAdmin->last_name,
            'email' => $this->superAdmin->email,
            'phone' => $this->superAdmin->phone,
            'preferred_language' => $this->superAdmin->preferred_language,
            'role' => UserRole::ADMIN->value,
            'is_active' => '0',
            'password' => null,
        ])->assertSessionHasErrors('role');

        $this->assertTrue($this->superAdmin->fresh()->isSuperAdmin());
        $this->assertTrue($this->superAdmin->fresh()->is_active);
    }

    public function test_super_admin_can_update_brand_assets(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superAdmin)
            ->put(route('admin.settings.update'), [
                'settings' => [
                    'company_name' => 'IGNA Studio',
                    'support_email' => 'admin@ignastudio.com',
                    'brand_logo_text' => 'IS',
                    'brand_logo_path' => null,
                    'brand_favicon_path' => null,
                    'storage_backend' => 'google_drive_stub',
                ],
                'brand_logo' => UploadedFile::fake()->image('logo.png', 256, 256),
                'brand_favicon' => UploadedFile::fake()->image('favicon.png', 64, 64),
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertDatabaseHas('settings', [
            'key' => 'brand_logo_text',
            'value' => 'IS',
        ]);

        $logoPath = Setting::query()->where('key', 'brand_logo_path')->value('value');
        $faviconPath = Setting::query()->where('key', 'brand_favicon_path')->value('value');

        Storage::disk('public')->assertExists($logoPath);
        Storage::disk('public')->assertExists($faviconPath);
    }

    public function test_admin_can_manage_team_profile_and_private_credential_document(): void
    {
        Storage::fake('local');

        $this->actingAs($this->superAdmin);

        $response = $this->post(route('admin.team.store'), [
            'name' => 'Mariana Torres',
            'slug' => 'mariana-torres',
            'role' => 'Engineering coordinator',
            'short_description' => 'Coordinates technical documentation and client follow-up.',
            'bio' => "Water infrastructure coordination\nProject documentation",
            'expertise' => "Hydraulic planning\nClient communication",
            'is_active' => '1',
            'sort_order' => 3,
        ]);

        $member = TeamMember::query()->where('slug', 'mariana-torres')->firstOrFail();

        $response->assertRedirect(route('admin.team.edit', $member));
        $this->assertDatabaseHas('team_members', [
            'id' => $member->id,
            'slug' => 'mariana-torres',
        ]);

        $this->post(route('admin.team.credentials.store', $member), [
            'title' => 'Civil engineering diploma',
            'institution' => 'Universidad Nacional',
            'issued_at' => '2024-02-01',
            'document' => UploadedFile::fake()->image('diploma.jpg', 900, 1200),
            'is_public' => '1',
            'sort_order' => 1,
        ])->assertRedirect(route('admin.team.edit', $member));

        $credential = $member->credentials()->firstOrFail();

        Storage::disk('local')->assertExists($credential->document_path);

        $viewUrl = URL::temporarySignedRoute('team.credentials.show', now()->addMinutes(5), [
            'teamMember' => $member,
            'credential' => $credential,
        ]);

        $this->get($viewUrl)
            ->assertOk()
            ->assertSee('Civil engineering diploma');

        $this->assertSame(1, TeamCredentialView::query()->where('team_credential_id', $credential->id)->count());

        $previewUrl = URL::temporarySignedRoute('team.credentials.preview', now()->addMinutes(5), [
            'teamMember' => $member,
            'credential' => $credential,
            'page' => 1,
        ]);

        $this->get($previewUrl)
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
    }

    public function test_admin_can_create_proposal_with_calculated_totals(): void
    {
        $client = User::factory()->create([
            'role' => UserRole::CLIENT,
            'is_active' => true,
            'phone' => '+57 300 555 1212',
        ]);

        $this->actingAs($this->superAdmin);

        $this->post(route('admin.proposals.store'), [
            'client_user_id' => $client->id,
            'title' => 'Water system assessment',
            'subject' => 'Proposal for initial technical diagnosis',
            'description' => 'We will review the available inputs and define the next technical steps.',
            'scope' => 'Initial review, findings summary, and recommendations.',
            'timeline_months' => 1,
            'timeline_weeks' => 3,
            'payment_schedule' => [
                ['label' => 'Start', 'percentage' => '50'],
                ['label' => 'Delivery', 'percentage' => '50'],
            ],
            'status' => 'draft',
            'tax_rate' => '19',
            'issued_at' => now()->toDateString(),
            'valid_until' => now()->addDays(15)->toDateString(),
            'items' => [
                ['category' => 'Studies', 'item_code' => 'ST-01', 'description' => 'Technical diagnosis', 'unit' => 'unit', 'quantity' => '2', 'unit_value' => '500000'],
                ['category' => 'Reports', 'item_code' => 'RP-01', 'description' => 'Recommendations report', 'unit' => 'unit', 'quantity' => '1', 'unit_value' => '300000'],
            ],
        ])->assertRedirect();

        $proposal = Proposal::query()->where('client_user_id', $client->id)->firstOrFail();

        $this->assertSame('IGNA-'.now()->format('Y').'-1042', $proposal->proposal_number);
        $this->assertEquals(1300000, (float) $proposal->subtotal);
        $this->assertEquals(247000, (float) $proposal->tax_total);
        $this->assertEquals(1547000, (float) $proposal->total);
        $this->assertSame(1, $proposal->timeline_months);
        $this->assertSame(3, $proposal->timeline_weeks);
        $this->assertEquals(50.0, $proposal->payment_schedule[0]['percentage']);
        $this->assertCount(2, $proposal->items);
        $this->assertNotEmpty($proposal->public_token);

        $this->get(route('admin.proposals.show', $proposal))
            ->assertOk()
            ->assertSee(__('site.generate_pdf'))
            ->assertSee(__('site.create_whatsapp_link'))
            ->assertSee(__('site.whatsapp_share_title'))
            ->assertSee(__('site.open_whatsapp'))
            ->assertSee(__('site.whatsapp_final_preview'))
            ->assertSee('data-whatsapp-panel', false)
            ->assertSee('data-whatsapp-preview', false)
            ->assertSee('*Water system assessment*')
            ->assertSee('300 555 1212')
            ->assertSee('proposals/public/'.$proposal->public_token, false)
            ->assertDontSee(__('site.print_proposal'))
            ->assertSee('Water system assessment')
            ->assertSee('Technical diagnosis');

        $this->get(route('admin.proposals.pdf', $proposal))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get(URL::signedRoute('proposals.public.show', $proposal))
            ->assertOk()
            ->assertSee('Water system assessment')
            ->assertSee('Technical diagnosis');

        $this->get(route('proposals.public.token.show', $proposal->public_token))
            ->assertOk()
            ->assertSee('Water system assessment')
            ->assertSee('Technical diagnosis');

        $this->get(route('proposals.public.show', $proposal))
            ->assertForbidden();
    }

    public function test_admin_can_create_proposal_for_unregistered_prospect(): void
    {
        $this->actingAs($this->superAdmin);

        $this->post(route('admin.proposals.store'), [
            'prospect_name' => 'Constructora Río Claro',
            'prospect_email' => 'proyectos@example.com',
            'prospect_phone' => '+57 310 000 1111',
            'title' => 'Prospect proposal',
            'subject' => 'Technical quote for prospect',
            'description' => 'Proposal for an unregistered prospect.',
            'scope' => 'Scope summary.',
            'timeline_months' => 1,
            'timeline_weeks' => 0,
            'payment_schedule' => [
                ['label' => 'Start', 'percentage' => '50'],
                ['label' => 'Delivery', 'percentage' => '50'],
            ],
            'status' => 'draft',
            'tax_rate' => '0',
            'issued_at' => now()->toDateString(),
            'items' => [
                ['category' => 'General', 'item_code' => 'P-01', 'description' => 'Prospect item', 'unit' => 'und', 'quantity' => '1', 'unit_value' => '250000'],
            ],
        ])->assertRedirect();

        $proposal = Proposal::query()->where('title', 'Prospect proposal')->firstOrFail();

        $this->assertNull($proposal->client_user_id);
        $this->assertSame('Constructora Río Claro', $proposal->clientDisplayName());

        $this->get(route('admin.proposals.show', $proposal))
            ->assertOk()
            ->assertSee('Constructora Río Claro')
            ->assertSee('310 000 1111')
            ->assertSee('proposals/public/'.$proposal->public_token, false);

        $this->get(route('proposals.public.token.show', $proposal->public_token))
            ->assertOk()
            ->assertSee('Constructora Río Claro')
            ->assertSee('Prospect item');
    }

    public function test_legacy_proposal_without_public_token_self_heals_on_admin_detail(): void
    {
        $client = User::factory()->create([
            'role' => UserRole::CLIENT,
            'is_active' => true,
        ]);

        $this->actingAs($this->superAdmin);

        $this->post(route('admin.proposals.store'), [
            'client_user_id' => $client->id,
            'title' => 'Legacy proposal',
            'subject' => 'Token backfill check',
            'description' => 'Existing proposal without token.',
            'scope' => 'Scope summary.',
            'timeline_months' => 1,
            'timeline_weeks' => 0,
            'payment_schedule' => [
                ['label' => 'Start', 'percentage' => '50'],
                ['label' => 'Delivery', 'percentage' => '50'],
            ],
            'status' => 'draft',
            'tax_rate' => '0',
            'issued_at' => now()->toDateString(),
            'items' => [
                ['category' => 'General', 'item_code' => 'L-01', 'description' => 'Legacy item', 'unit' => 'und', 'quantity' => '1', 'unit_value' => '100000'],
            ],
        ])->assertRedirect();

        $proposal = Proposal::query()->where('title', 'Legacy proposal')->firstOrFail();
        $proposal->forceFill(['public_token' => null])->saveQuietly();

        $this->get(route('admin.proposals.show', $proposal))
            ->assertOk()
            ->assertSee('Legacy proposal')
            ->assertSee('proposals/public/', false);

        $this->assertNotEmpty($proposal->fresh()->public_token);
    }

    public function test_proposal_form_uses_service_templates_instead_of_excel_upload(): void
    {
        $this->actingAs($this->superAdmin);

        $this->assertDatabaseHas('proposal_service_templates', [
            'service_number' => 1,
            'code' => 'ENG-001',
        ]);

        $create = $this->get(route('admin.proposals.create'))
            ->assertOk()
            ->assertSee(__('site.add_item'))
            ->assertSee(__('site.add_payment'))
            ->assertSee(__('site.select_service_template'))
            ->assertSee('data-proposal-template-select', false)
            ->assertSee('data-template-replace-message', false)
            ->assertSee(__('site.proposal_description_template_help'))
            ->assertSee(__('site.proposal_timeline_help'))
            ->assertSee(__('site.grand_total_value'))
            ->assertDontSee(__('site.upload_excel_file'))
            ->assertDontSee('proposal-excel-upload', false)
            ->assertDontSee('name="validity_days"', false)
            ->assertSee('name="issued_at"', false)
            ->assertSee('name="valid_until"', false);

        $create->assertSee('proposal-payment-row', false);
        $this->assertSame(2, substr_count($create->getContent(), 'data-existing-row="payment"'));
        $this->assertSame(2, substr_count($create->getContent(), 'data-existing-row="item"'));

        $this->post(route('admin.proposals.store'), [
            'title' => 'Proposal layout check',
            'subject' => 'Internal layout verification',
            'description' => 'Short description.',
            'scope' => 'Short scope.',
            'timeline_months' => 1,
            'timeline_weeks' => 0,
            'payment_schedule' => [
                ['label' => 'Start', 'percentage' => '50'],
                ['label' => 'Delivery', 'percentage' => '50'],
            ],
            'status' => 'draft',
            'tax_rate' => '0',
            'issued_at' => now()->toDateString(),
            'items' => [
                ['category' => 'General', 'item_code' => 'G-01', 'description' => 'Layout item', 'unit' => 'und', 'quantity' => '1', 'unit_value' => '100000'],
                ['category' => '', 'item_code' => 'Optional', 'description' => 'Optional non-costed service', 'unit' => '', 'quantity' => '', 'unit_value' => ''],
            ],
        ])->assertRedirect();

        $proposal = Proposal::query()->where('title', 'Proposal layout check')->firstOrFail();

        $this->get(route('admin.proposals.show', $proposal))
            ->assertOk()
            ->assertSee(__('site.generate_pdf'))
            ->assertSee(__('site.proposal_terms_title'))
            ->assertDontSee(__('site.upload_excel_file'));

        $this->get(route('admin.proposals.edit', $proposal))
            ->assertOk()
            ->assertSee(__('site.select_service_template'))
            ->assertDontSee(__('site.upload_excel_file'))
            ->assertDontSee(__('site.excel_upload_help'))
            ->assertDontSee('proposal-excel-upload', false)
            ->assertDontSee('name="validity_days"', false)
            ->assertSee('name="issued_at"', false)
            ->assertSee('name="valid_until"', false);

        $this->post("/admin/proposals/{$proposal->id}/excel")->assertNotFound();

        $this->get(route('admin.proposals.index'))
            ->assertOk()
            ->assertSee('rounded-full bg-olive-50', false)
            ->assertSee($proposal->proposal_number);
    }

    public function test_proposal_validity_is_calculated_from_valid_until_and_displayed(): void
    {
        $client = User::factory()->create([
            'role' => UserRole::CLIENT,
            'is_active' => true,
        ]);

        $this->actingAs($this->superAdmin);

        $this->post(route('admin.proposals.store'), [
            'client_user_id' => $client->id,
            'title' => 'Sanitary network quote',
            'subject' => 'Proposal for network design',
            'description' => 'Technical proposal content.',
            'scope' => 'Network design and deliverables.',
            'timeline_months' => 1,
            'timeline_weeks' => 0,
            'payment_schedule' => [
                ['label' => 'Anticipo', 'percentage' => '40'],
                ['label' => 'Acta parcial', 'percentage' => '40'],
                ['label' => 'Cierre', 'percentage' => '20'],
            ],
            'status' => 'draft',
            'tax_rate' => '19',
            'issued_at' => '2026-05-16',
            'valid_until' => '2026-06-15',
            'items' => [
                ['category' => 'Instalaciones de redes sanitarias', 'item_code' => '2.1', 'description' => 'Diseño sanitario', 'unit' => 'und', 'quantity' => '1', 'unit_value' => '1000000'],
            ],
        ])->assertRedirect();

        $proposal = Proposal::query()->where('title', 'Sanitary network quote')->firstOrFail();

        $this->assertSame(30, $proposal->validity_days);
        $this->assertSame('2026-06-15', $proposal->valid_until?->toDateString());

        $this->get(route('admin.proposals.show', $proposal))
            ->assertOk()
            ->assertSee(__('site.valid_until').': 2026-06-15')
            ->assertSee('2026-06-15');
    }

    public function test_admin_panel_locale_switch_changes_dashboard_copy(): void
    {
        $this->actingAs($this->superAdmin);

        $this->withSession(['locale' => 'es'])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Solicitudes abiertas')
            ->assertDontSee('Open tickets');

        $this->post(route('locale.switch', 'en'))->assertRedirect();

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Open tickets')
            ->assertDontSee('Solicitudes abiertas');
    }

    public function test_demo_seed_data_populates_admin_sections(): void
    {
        $this->actingAs($this->superAdmin);

        $this->assertDatabaseHas('users', ['email' => 'cliente.digital@ignastudio.test']);
        $this->assertDatabaseHas('tickets', ['project_name' => 'Portal de seguimiento comercial']);
        $this->assertDatabaseHas('ticket_files', ['title' => 'Alcance funcional inicial', 'is_client_visible' => true]);
        $this->assertDatabaseHas('blog_posts', ['slug' => 'trazabilidad-operativa-en-servicios-tecnicos']);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Portal de seguimiento comercial');
    }

    public function test_client_can_download_available_demo_file(): void
    {
        $client = User::query()->where('email', 'cliente.digital@ignastudio.test')->firstOrFail();
        $ticket = Ticket::query()->where('project_name', 'Portal de seguimiento comercial')->firstOrFail();
        $file = $ticket->files()->where('is_client_visible', true)->firstOrFail();

        $this->actingAs($client)
            ->get(route('client.tickets.files.download', [$ticket, $file]))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_public_tracking_download_uses_signed_available_file_link(): void
    {
        $ticket = Ticket::query()->where('project_name', 'Portal de seguimiento comercial')->firstOrFail();
        $file = $ticket->files()->where('is_client_visible', true)->firstOrFail();

        $url = URL::temporarySignedRoute('tracking.files.download', now()->addMinutes(5), [
            'ticket' => $ticket,
            'file' => $file,
            'email_hash' => hash('sha256', strtolower($ticket->email)),
        ]);

        $this->get($url)
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_file_download_rejects_untrusted_external_urls(): void
    {
        $this->actingAs($this->superAdmin);

        $ticket = Ticket::query()->firstOrFail();
        $file = $ticket->files()->create([
            'title' => 'Unsafe redirect',
            'original_name' => 'unsafe.pdf',
            'storage_provider' => 'google_drive_stub',
            'google_drive_url' => 'https://example.com/unsafe.pdf',
            'is_client_visible' => true,
            'uploaded_at' => now(),
        ]);

        $this->get(route('admin.tickets.files.download', [$ticket, $file]))
            ->assertNotFound();
    }

    public function test_oversized_upload_gets_friendly_error_page(): void
    {
        $this->actingAs($this->superAdmin);

        $ticket = Ticket::query()->firstOrFail();

        $this->withServerVariables(['CONTENT_LENGTH' => (string) (30 * 1024 * 1024)])
            ->post(route('admin.tickets.files.store', $ticket), [])
            ->assertStatus(413)
            ->assertSee('El archivo cargado es demasiado grande');
    }

    private function createTicket(): Ticket
    {
        $service = Service::query()->firstOrFail();

        $this->post(route('requests.store'), [
            'first_name' => 'Public',
            'last_name' => 'Client',
            'email' => 'public.client@example.com',
            'phone' => '+57 300 123 4567',
            'project_name' => 'Assigned Portal Project',
            'project_location' => 'Bogota',
            'preferred_language' => 'es',
            'service_id' => $service->id,
            'project_description' => 'A project request that will be linked to a client account.',
            'target_date' => now()->addWeeks(2)->toDateString(),
        ]);

        return Ticket::query()
            ->where('project_name', 'Assigned Portal Project')
            ->where('email', 'public.client@example.com')
            ->latest('id')
            ->firstOrFail();
    }
}
