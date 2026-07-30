<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\AdminNewTicketMail;
use App\Mail\ProjectUpdateMail;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Services\PublicServiceTaxonomy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicServiceTaxonomyRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Mail::fake();
        $this->seed();

        $this->superAdmin = User::query()->where('role', UserRole::SUPER_ADMIN)->firstOrFail();
    }

    public function test_public_request_selector_renders_grouped_english_categories_in_order(): void
    {
        $this->post(route('locale.switch', 'en'));

        $html = $this->get('/')
            ->assertOk()
            ->assertSee('<optgroup label="Technology">', false)
            ->assertSee('<optgroup label="Infrastructure Engineering">', false)
            ->assertSee('Other / I am not sure')
            ->getContent();

        $this->assertStringContainsString('name="service_id"', $html);
        $this->assertLessThan(
            strpos($html, '<optgroup label="Infrastructure Engineering">'),
            strpos($html, '<optgroup label="Technology">'),
        );
        $this->assertLessThan(
            strpos($html, 'Other / I am not sure'),
            strpos($html, '<optgroup label="Infrastructure Engineering">'),
        );
    }

    public function test_public_request_selector_renders_grouped_spanish_categories(): void
    {
        $this->post(route('locale.switch', 'es'));

        $this->get('/')
            ->assertOk()
            ->assertSee('<optgroup label="Tecnología">', false)
            ->assertSee('<optgroup label="Ingeniería de Infraestructura">', false)
            ->assertSee('Otra solicitud / No estoy seguro');
    }

    public function test_category_codes_are_language_neutral_and_uncategorized_services_fall_back_to_other(): void
    {
        $taxonomy = app(PublicServiceTaxonomy::class);
        $service = $this->service(['business_line' => 'legacy_unknown', 'code' => 'UNK']);

        $this->assertSame(['technology', 'infrastructure_engineering', 'other'], $taxonomy->codes());
        $this->assertSame('other', $service->publicCategoryCode());
        $this->assertTrue($taxonomy->groupServices(collect([$service]))->has('other'));
    }

    public function test_inactive_services_and_empty_groups_are_omitted_from_public_selector(): void
    {
        Service::query()->where('business_line', 'engineering')->update(['is_active' => false]);
        $inactive = Service::query()->where('business_line', 'engineering')->firstOrFail();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('<optgroup label="Infrastructure Engineering">', false)
            ->assertDontSee($inactive->localizedName());
    }

    public function test_superadministrator_can_assign_public_categories_and_invalid_values_are_rejected(): void
    {
        $service = Service::query()->firstOrFail();

        $this->actingAs($this->superAdmin)
            ->put(route('admin.services.update', $service), $this->servicePayload($service, [
                'business_line' => 'engineering',
                'service_type' => 'hydrology',
                'service_scope' => 'study',
            ]))
            ->assertRedirect(route('admin.services.edit', $service));

        $this->assertSame('infrastructure_engineering', $service->fresh()->publicCategoryCode());

        $this->actingAs($this->superAdmin)
            ->from(route('admin.services.edit', $service))
            ->put(route('admin.services.update', $service), $this->servicePayload($service, [
                'business_line' => 'unsupported',
                'service_type' => 'hydrology',
                'service_scope' => 'study',
                'code' => '',
            ]))
            ->assertRedirect(route('admin.services.edit', $service))
            ->assertSessionHasErrors(['business_line', 'code'])
            ->assertSessionHasInput('business_line', 'unsupported');
    }

    public function test_updating_category_preserves_service_translations_deliverables_and_stages(): void
    {
        $service = $this->service([
            'name_en' => 'Local AI Planning',
            'name_es' => 'Planeacion IA local',
            'description_en' => 'English text',
            'description_es' => 'Texto espanol',
        ]);
        $service->deliverables()->create([
            'name' => 'Roadmap',
            'name_en' => 'Roadmap',
            'name_es' => 'Hoja de ruta',
            'sort_order' => 1,
            'is_active' => true,
            'is_client_visible_by_default' => true,
        ]);
        $stage = $service->stages()->create([
            'name' => 'Discovery',
            'name_en' => 'Discovery',
            'name_es' => 'Descubrimiento',
            'code' => 'DSC',
            'sort_order' => 1,
            'is_active' => true,
            'is_client_visible' => true,
        ]);

        $this->actingAs($this->superAdmin)
            ->put(route('admin.services.update', $service), $this->servicePayload($service, [
                'business_line' => 'engineering',
                'service_type' => 'hydrology',
                'service_scope' => 'study',
                'deliverables' => [
                    ['en' => 'Roadmap', 'es' => 'Hoja de ruta'],
                ],
            ]))
            ->assertRedirect(route('admin.services.edit', $service));

        $service->refresh();

        $this->assertSame('Local AI Planning', $service->name_en);
        $this->assertSame('Planeacion IA local', $service->name_es);
        $this->assertSame('Hoja de ruta', $service->deliverables()->firstOrFail()->name_es);
        $this->assertSame('Descubrimiento', $stage->fresh()->name_es);
    }

    public function test_public_request_submits_catalog_services_and_rejects_headings_or_inactive_services(): void
    {
        $technology = Service::query()->where('business_line', 'digital')->firstOrFail();
        $engineering = Service::query()->where('business_line', 'engineering')->firstOrFail();

        $this->post(route('requests.store'), $this->requestPayload(['service_id' => (string) $technology->id]))
            ->assertRedirect(route('tracking.index'));
        $this->post(route('requests.store'), $this->requestPayload([
            'service_id' => (string) $engineering->id,
            'project_name' => 'Infrastructure request',
        ]))->assertRedirect(route('tracking.index'));

        $this->assertDatabaseHas('tickets', [
            'project_name' => 'Technology request',
            'service_id' => $technology->id,
            'service_selection' => 'catalog',
            'service_public_category' => 'technology',
        ]);
        $this->assertDatabaseHas('tickets', [
            'project_name' => 'Infrastructure request',
            'service_id' => $engineering->id,
            'service_selection' => 'catalog',
            'service_public_category' => 'infrastructure_engineering',
        ]);

        $this->post(route('requests.store'), $this->requestPayload(['service_id' => 'technology']))
            ->assertSessionHasErrors('service_id');

        $technology->update(['is_active' => false]);
        $this->post(route('requests.store'), $this->requestPayload(['service_id' => (string) $technology->id]))
            ->assertSessionHasErrors('service_id');
    }

    public function test_other_request_submits_without_creating_a_fake_service_record(): void
    {
        app()->setLocale('en');

        $serviceCount = Service::query()->count();

        $this->post(route('requests.store'), $this->requestPayload([
            'service_id' => 'other',
            'project_name' => 'Other request',
        ]))->assertRedirect(route('tracking.index'));

        $ticket = Ticket::query()
            ->where('project_name', 'Other request')
            ->firstOrFail();

        $this->assertNull($ticket->service_id);
        $this->assertSame('other', $ticket->service_selection);
        $this->assertSame('other', $ticket->service_public_category);
        $this->assertSame('Other / I am not sure', $ticket->serviceDisplayName());
        $this->assertCount(0, $ticket->stageEvents);
        $this->assertSame($serviceCount, Service::query()->count());

        Mail::assertSent(ProjectUpdateMail::class, fn (ProjectUpdateMail $mail): bool => $mail->type === 'request_received'
            && str_contains($mail->render(), 'Other / I am not sure'));
        Mail::assertSent(AdminNewTicketMail::class, fn (AdminNewTicketMail $mail): bool => str_contains($mail->render(), 'Other / I am not sure'));
    }

    public function test_selected_service_survives_validation_errors(): void
    {
        $service = Service::query()->where('business_line', 'digital')->firstOrFail();

        $this->from('/#request')
            ->post(route('requests.store'), $this->requestPayload([
                'service_id' => (string) $service->id,
                'email' => 'not-an-email',
            ]))
            ->assertRedirect('/#request')
            ->assertSessionHasErrors('email')
            ->assertSessionHasInput('service_id', (string) $service->id);

        $this->from('/#request')
            ->post(route('requests.store'), $this->requestPayload([
                'service_id' => 'other',
                'email' => 'not-an-email',
            ]))
            ->assertRedirect('/#request')
            ->assertSessionHasErrors('email')
            ->assertSessionHasInput('service_id', 'other');
    }

    private function service(array $overrides = []): Service
    {
        return Service::query()->create([
            'name' => $overrides['name'] ?? 'Local Taxonomy Service',
            'name_en' => $overrides['name_en'] ?? 'Local Taxonomy Service',
            'name_es' => $overrides['name_es'] ?? 'Servicio local de taxonomia',
            'slug' => $overrides['slug'] ?? 'local-taxonomy-service-'.fake()->unique()->numberBetween(1000, 9999),
            'code' => $overrides['code'] ?? 'LTX'.fake()->unique()->numberBetween(100, 999),
            'business_line' => $overrides['business_line'] ?? 'digital',
            'service_type' => $overrides['service_type'] ?? 'web_platform',
            'service_scope' => $overrides['service_scope'] ?? 'none',
            'description' => $overrides['description'] ?? 'Local taxonomy service.',
            'description_en' => $overrides['description_en'] ?? 'Local taxonomy service.',
            'description_es' => $overrides['description_es'] ?? 'Servicio local de taxonomia.',
            'deliverables_schema' => [],
            'is_active' => $overrides['is_active'] ?? true,
            'sort_order' => $overrides['sort_order'] ?? 90,
        ]);
    }

    private function servicePayload(Service $service, array $overrides = []): array
    {
        return array_replace_recursive([
            'name_en' => $service->name_en ?: $service->name,
            'name_es' => $service->name_es,
            'code' => $service->code,
            'business_line' => $service->business_line,
            'service_type' => $service->service_type,
            'service_scope' => $service->service_scope,
            'description_en' => $service->description_en ?: $service->description,
            'description_es' => $service->description_es,
            'deliverables' => $service->deliverables()->get()->map(fn ($deliverable): array => [
                'en' => $deliverable->name_en ?: $deliverable->name,
                'es' => $deliverable->name_es,
            ])->values()->all(),
            'is_active' => $service->is_active ? '1' : '0',
        ], $overrides);
    }

    private function requestPayload(array $overrides = []): array
    {
        return array_replace([
            'first_name' => 'Public',
            'last_name' => 'Client',
            'email' => 'public.client@example.com',
            'phone' => '+57 300 123 4567',
            'project_name' => 'Technology request',
            'project_location' => 'Bogota',
            'preferred_language' => 'en',
            'service_id' => (string) Service::query()->where('is_active', true)->firstOrFail()->id,
            'project_description' => 'Synthetic local request for grouped public service selector validation.',
            'target_date' => now()->addWeeks(2)->toDateString(),
        ], $overrides);
    }
}
