<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\BlogPost;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertForbidden();
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

        $this->put(route('admin.tickets.files.visibility.update', [$ticket, $file]))
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $this->assertTrue($file->fresh()->is_client_visible);
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

        return Ticket::query()->latest()->firstOrFail();
    }
}
