<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Service;
use App\Models\ServiceStage;
use App\Models\User;
use App\Services\Services\ServiceContentTranslator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceAdministrationStructuredContentTest extends TestCase
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

    public function test_service_administration_is_available_only_to_authorized_users(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.services.index'))
            ->assertOk();

        auth()->logout();
        $this->flushSession();

        $this->get(route('admin.services.index'))
            ->assertRedirect(route('login'));

        $client = User::factory()->create(['role' => UserRole::CLIENT]);

        $this->actingAs($client)
            ->post(route('admin.services.store'), $this->servicePayload())
            ->assertForbidden();
    }

    public function test_service_translation_populates_names_and_deliverables_without_overwriting_reviewed_targets(): void
    {
        $service = $this->localService([
            'name_en' => 'Reviewed English',
            'name_es' => 'Servicio claro',
            'description_en' => 'Reviewed description',
            'description_es' => 'Descripcion clara',
        ]);
        $service->deliverables()->create([
            'name' => 'Entregable tecnico',
            'name_es' => 'Entregable tecnico',
            'name_en' => 'Reviewed deliverable',
            'sort_order' => 1,
            'is_active' => true,
            'is_client_visible_by_default' => true,
        ]);

        $this->fakeTranslator();

        $this->actingAs($this->superAdmin)
            ->put(route('admin.services.translate', $service), [
                'source_locale' => 'es',
                'name_en' => 'Reviewed English',
                'name_es' => 'Servicio claro',
                'description_en' => 'Reviewed description',
                'description_es' => 'Descripcion clara',
                'deliverables' => [
                    ['en' => 'Reviewed deliverable', 'es' => 'Entregable tecnico'],
                ],
            ])
            ->assertRedirect(route('admin.services.edit', $service))
            ->assertSessionHasInput('name_es', 'Servicio claro')
            ->assertSessionHasInput('name_en', 'Reviewed English')
            ->assertSessionHasInput('deliverables.0.en', 'Reviewed deliverable');

        $this->actingAs($this->superAdmin)
            ->put(route('admin.services.translate', $service), [
                'source_locale' => 'en',
                'overwrite' => '1',
                'name_en' => 'English source',
                'name_es' => '',
                'description_en' => 'English description',
                'description_es' => '',
                'deliverables' => [
                    ['en' => 'Independent deliverable', 'es' => ''],
                ],
            ])
            ->assertRedirect(route('admin.services.edit', $service))
            ->assertSessionHasInput('name_en', 'English source')
            ->assertSessionHasInput('name_es', 'es: English source')
            ->assertSessionHasInput('deliverables.0.es', 'es: Independent deliverable');
    }

    public function test_failed_service_translation_preserves_submitted_values(): void
    {
        $service = $this->localService();
        $this->fakeTranslator(failOn: 'romper');

        $this->actingAs($this->superAdmin)
            ->put(route('admin.services.translate', $service), [
                'source_locale' => 'es',
                'overwrite' => '1',
                'name_en' => 'Stable English',
                'name_es' => 'romper',
                'description_en' => 'Stable description',
                'description_es' => 'Descripcion',
                'deliverables' => [
                    ['en' => 'Stable row', 'es' => 'Fila estable'],
                ],
            ])
            ->assertRedirect(route('admin.services.edit', $service))
            ->assertSessionHasErrors('translation')
            ->assertSessionHasInput('name_en', 'Stable English')
            ->assertSessionHasInput('name_es', 'romper')
            ->assertSessionHasInput('deliverables.0.es', 'Fila estable');
    }

    public function test_structured_deliverables_persist_order_and_remove_blank_rows(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.services.store'), $this->servicePayload([
                'deliverables' => [
                    ['en' => '  First report  ', 'es' => 'Primer informe'],
                    ['en' => '', 'es' => ''],
                    ['en' => 'Second model', 'es' => 'Segundo modelo'],
                ],
            ]))
            ->assertRedirect(route('admin.services.index'));

        $service = Service::query()->where('code', 'LST')->firstOrFail();

        $this->assertSame(['First report', 'Second model'], $service->deliverables_schema);
        $this->assertSame(['First report', 'Second model'], $service->deliverables()->pluck('name_en')->all());
        $this->assertSame(['Primer informe', 'Segundo modelo'], $service->deliverables()->pluck('name_es')->all());
    }

    public function test_legacy_newline_and_pipe_deliverables_normalize_without_splitting_slashes(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.services.store'), $this->servicePayload([
                'deliverables' => "Project report | Calculation memo\nA/B testing plan",
            ]))
            ->assertRedirect(route('admin.services.index'));

        $service = Service::query()->where('code', 'LST')->firstOrFail();

        $this->assertSame(['Project report', 'Calculation memo', 'A/B testing plan'], $service->deliverables_schema);
        $this->assertSame(['Project report', 'Calculation memo', 'A/B testing plan'], $service->fresh()->localizedDeliverables());
    }

    public function test_validation_error_retains_bilingual_names_and_deliverable_rows(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('admin.services.create'))
            ->post(route('admin.services.store'), $this->servicePayload([
                'code' => '',
                'name_en' => 'English draft',
                'name_es' => 'Borrador espanol',
                'deliverables' => [
                    ['en' => 'English row', 'es' => 'Fila espanola'],
                ],
            ]))
            ->assertRedirect(route('admin.services.create'))
            ->assertSessionHasErrors('code')
            ->assertSessionHasInput('name_en', 'English draft')
            ->assertSessionHasInput('name_es', 'Borrador espanol')
            ->assertSessionHasInput('deliverables.0.en', 'English row')
            ->assertSessionHasInput('deliverables.0.es', 'Fila espanola');
    }

    public function test_public_localized_deliverables_use_spanish_pairs_when_present(): void
    {
        $service = $this->localService();
        $service->deliverables()->createMany([
            [
                'name' => 'First report',
                'name_en' => 'First report',
                'name_es' => 'Primer informe',
                'sort_order' => 1,
                'is_active' => true,
                'is_client_visible_by_default' => true,
            ],
            [
                'name' => 'Second model',
                'name_en' => 'Second model',
                'name_es' => 'Segundo modelo',
                'sort_order' => 2,
                'is_active' => true,
                'is_client_visible_by_default' => true,
            ],
        ]);

        app()->setLocale('es');

        $this->assertSame(['Primer informe', 'Segundo modelo'], $service->fresh('deliverables')->localizedDeliverables());
    }

    public function test_workflow_stages_store_bilingual_values_and_preserve_other_stages(): void
    {
        $service = $this->localService();
        $first = $service->stages()->create([
            'name' => 'Discovery',
            'name_en' => 'Discovery',
            'name_es' => 'Descubrimiento',
            'code' => 'DSC',
            'sort_order' => 1,
            'is_active' => true,
            'is_client_visible' => true,
        ]);
        $second = $service->stages()->create([
            'name' => 'Delivery',
            'name_en' => 'Delivery',
            'name_es' => 'Entrega',
            'code' => 'DLV',
            'sort_order' => 2,
            'is_active' => true,
            'is_client_visible' => true,
        ]);

        $this->actingAs($this->superAdmin)
            ->put(route('admin.services.stages.update', [$service, $first]), [
                'name_en' => 'Discovery updated',
                'name_es' => 'Descubrimiento actualizado',
                'code' => 'DSC',
                'description_en' => 'Updated English',
                'description_es' => 'Actualizado espanol',
                'sort_order' => 3,
                'is_active' => '1',
                'is_client_visible' => '1',
            ])
            ->assertRedirect(route('admin.services.edit', $service));

        $this->assertSame('Discovery updated', $first->fresh()->name_en);
        $this->assertSame(3, $first->fresh()->sort_order);
        $this->assertSame('Delivery', $second->fresh()->name_en);
        $this->assertSame(2, $second->fresh()->sort_order);
    }

    public function test_stage_translation_and_removal_are_scoped_to_the_selected_stage(): void
    {
        $service = $this->localService();
        $first = $service->stages()->create([
            'name' => 'Revision',
            'name_es' => 'Revision',
            'code' => 'REV',
            'description_es' => 'Descripcion de revision',
            'sort_order' => 1,
            'is_active' => true,
            'is_client_visible' => true,
        ]);
        $second = $service->stages()->create([
            'name' => 'Approval',
            'name_en' => 'Approval',
            'code' => 'APP',
            'sort_order' => 2,
            'is_active' => true,
            'is_client_visible' => true,
        ]);

        $this->fakeTranslator();

        $this->actingAs($this->superAdmin)
            ->put(route('admin.services.stages.translate', [$service, $first]), [
                'source_locale' => 'es',
                'name_es' => 'Revision',
                'name_en' => '',
                'code' => 'REV',
                'description_es' => 'Descripcion de revision',
                'description_en' => '',
                'sort_order' => 1,
                'is_active' => '1',
                'is_client_visible' => '1',
            ])
            ->assertRedirect(route('admin.services.edit', $service))
            ->assertSessionHasInput('editing_stage_id', $first->id)
            ->assertSessionHasInput('name_en', 'en: Revision')
            ->assertSessionHasInput('description_en', 'en: Descripcion de revision');

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.services.stages.destroy', [$service, $first]))
            ->assertRedirect(route('admin.services.edit', $service));

        $this->assertModelMissing($first);
        $this->assertModelExists($second);
    }

    private function localService(array $overrides = []): Service
    {
        return Service::query()->create([
            'name' => $overrides['name'] ?? 'Local Structured Service',
            'name_en' => $overrides['name_en'] ?? 'Local Structured Service',
            'name_es' => $overrides['name_es'] ?? 'Servicio estructurado local',
            'slug' => 'local-structured-service',
            'code' => $overrides['code'] ?? 'LST',
            'business_line' => 'digital',
            'service_type' => 'web_platform',
            'service_scope' => 'none',
            'description' => $overrides['description'] ?? 'Local service for structured content tests.',
            'description_en' => $overrides['description_en'] ?? 'Local service for structured content tests.',
            'description_es' => $overrides['description_es'] ?? 'Servicio local para pruebas de contenido estructurado.',
            'deliverables_schema' => [],
            'is_active' => true,
            'sort_order' => 99,
        ]);
    }

    private function servicePayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'name_en' => 'Local Structured Service',
            'name_es' => 'Servicio estructurado local',
            'code' => 'LST',
            'business_line' => 'digital',
            'service_type' => 'web_platform',
            'service_scope' => 'none',
            'description_en' => 'Local service for structured content tests.',
            'description_es' => 'Servicio local para pruebas de contenido estructurado.',
            'deliverables' => [
                ['en' => 'Discovery note', 'es' => 'Nota de descubrimiento'],
            ],
            'is_active' => '1',
        ], $overrides);
    }

    private function fakeTranslator(?string $failOn = null): void
    {
        app()->instance(ServiceContentTranslator::class, new class($failOn) extends ServiceContentTranslator
        {
            public function __construct(private readonly ?string $failOn) {}

            public function translate(?string $value, string $sourceLocale, string $targetLocale): string
            {
                if ($this->failOn !== null && str_contains((string) $value, $this->failOn)) {
                    throw new \RuntimeException('Simulated translation failure.');
                }

                return $targetLocale.': '.trim((string) $value);
            }
        });
    }
}
