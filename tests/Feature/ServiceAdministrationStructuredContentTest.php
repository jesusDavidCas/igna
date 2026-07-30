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

    public function test_service_edit_shows_single_visible_content_fields_without_duplicate_locale_controls(): void
    {
        $service = $this->localService();
        $service->deliverables()->create([
            'name' => 'Visible deliverable',
            'name_en' => 'Visible deliverable',
            'name_es' => 'Entregable visible',
            'sort_order' => 1,
            'is_active' => true,
            'is_client_visible_by_default' => true,
        ]);
        $service->stages()->create([
            'name' => 'Discovery',
            'name_en' => 'Discovery',
            'name_es' => 'Descubrimiento',
            'code' => 'DSC',
            'sort_order' => 1,
            'is_active' => true,
            'is_client_visible' => true,
        ]);

        $html = $this->actingAs($this->superAdmin)
            ->get(route('admin.services.edit', $service))
            ->assertOk()
            ->assertSee('name="content_locale"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="description"', false)
            ->assertSee('name="deliverables[0][content]"', false)
            ->assertDontSee(__('site.deliverable_en'))
            ->assertDontSee(__('site.deliverable_es'))
            ->assertDontSee(__('site.translate_from_english'))
            ->assertDontSee(__('site.translate_from_spanish'))
            ->assertDontSee(__('site.overwrite_translations'))
            ->getContent();

        $this->assertStringNotContainsString('>EN</label>', $html);
        $this->assertStringNotContainsString('>ES</label>', $html);
    }

    public function test_service_edit_uses_current_locale_values_for_parent_deliverables_and_stages(): void
    {
        $service = $this->localService([
            'name_en' => 'English service name',
            'name_es' => 'Nombre de servicio',
            'description_en' => 'English service description',
            'description_es' => 'Descripcion del servicio',
        ]);
        $service->deliverables()->create([
            'name' => 'English deliverable',
            'name_en' => 'English deliverable',
            'name_es' => 'Entregable espanol',
            'sort_order' => 1,
            'is_active' => true,
            'is_client_visible_by_default' => true,
        ]);
        $service->stages()->create([
            'name' => 'English stage',
            'name_en' => 'English stage',
            'name_es' => 'Etapa espanola',
            'code' => 'LOC',
            'description_en' => 'English stage description',
            'description_es' => 'Descripcion de etapa',
            'sort_order' => 1,
            'is_active' => true,
            'is_client_visible' => true,
        ]);

        $this->actingAs($this->superAdmin)
            ->withSession(['locale' => 'es'])
            ->get(route('admin.services.edit', $service))
            ->assertOk()
            ->assertSee('value="Nombre de servicio"', false)
            ->assertSee('Descripcion del servicio')
            ->assertSee('value="Entregable espanol"', false)
            ->assertSee('value="Etapa espanola"', false)
            ->assertSee('Descripcion de etapa');

        $this->actingAs($this->superAdmin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.services.edit', $service))
            ->assertOk()
            ->assertSee('value="English service name"', false)
            ->assertSee('English service description')
            ->assertSee('value="English deliverable"', false)
            ->assertSee('value="English stage"', false);
    }

    public function test_single_field_service_save_does_not_cache_copied_translation_when_provider_missing(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.services.store'), $this->servicePayload([
                'name' => 'Single source service',
                'name_en' => '',
                'name_es' => '',
                'description' => 'Single source service description',
                'description_en' => '',
                'description_es' => '',
                'deliverables' => [
                    ['content' => 'Single source deliverable', 'en' => '', 'es' => ''],
                ],
            ]))
            ->assertRedirect(route('admin.services.index'))
            ->assertSessionHas('warning', __('site.dynamic_translation_unavailable'));

        $service = Service::query()->where('code', 'LST')->firstOrFail();

        $this->assertSame('Single source service', $service->name_en);
        $this->assertNull($service->name_es);
        $this->assertSame('Single source deliverable', $service->deliverables()->firstOrFail()->name_en);
        $this->assertNull($service->deliverables()->firstOrFail()->name_es);
    }

    public function test_translate_missing_content_command_is_idempotent_and_requires_provider(): void
    {
        $service = $this->localService([
            'name_en' => 'Stormwater sewer design',
            'name_es' => 'Stormwater sewer design',
            'description_en' => 'External network design.',
            'description_es' => '',
        ]);
        $service->deliverables()->create([
            'name' => 'Hydraulic memo',
            'name_en' => 'Hydraulic memo',
            'name_es' => '',
            'sort_order' => 1,
            'is_active' => true,
            'is_client_visible_by_default' => true,
        ]);
        $service->stages()->create([
            'name' => 'Review',
            'name_en' => 'Review',
            'name_es' => '',
            'code' => 'REV',
            'description_en' => 'Internal review.',
            'description_es' => '',
            'sort_order' => 1,
            'is_active' => true,
            'is_client_visible' => true,
        ]);

        $this->artisan('content:translate-missing', ['--service' => $service->id, '--dry-run' => true])
            ->assertFailed();

        $this->assertSame('Stormwater sewer design', $service->fresh()->name_es);

        $this->artisan('content:translate-missing')
            ->assertFailed();

        $this->fakeTranslator();

        $this->artisan('content:translate-missing', ['--service' => $service->id, '--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame('Stormwater sewer design', $service->fresh()->name_es);

        $this->artisan('content:translate-missing', ['--service' => $service->id])
            ->assertSuccessful();

        $service->refresh()->load(['deliverables', 'stages']);

        $this->assertSame('es: Stormwater sewer design', $service->name_es);
        $this->assertSame('es: External network design.', $service->description_es);
        $this->assertSame('es: Hydraulic memo', $service->deliverables->first()->name_es);
        $this->assertSame('es: Review', $service->stages->first()->name_es);

        $this->artisan('content:translate-missing', ['--service' => $service->id])
            ->assertSuccessful();

        $this->assertSame('es: Stormwater sewer design', $service->fresh()->name_es);
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

    public function test_seeded_catalog_deliverables_switch_locale_for_service_graph(): void
    {
        $service = Service::query()
            ->with(['deliverables', 'stages'])
            ->where('code', 'PTP')
            ->firstOrFail();

        app()->setLocale('en');
        $this->assertSame('Prepare a plant or system to treat drinking water', $service->localizedName());
        $this->assertSame([
            'project descriptive report',
            'hydraulic calculations',
            'technical plans',
            'Resolution 799 of 2021 references',
        ], $service->localizedDeliverables());

        app()->setLocale('es');
        $this->assertSame('Preparar una planta o sistema para tratar agua potable', $service->localizedName());
        $this->assertSame([
            'memoria descriptiva del proyecto',
            'cálculos hidráulicos',
            'planos técnicos',
            'referencias de la Resolución 799 de 2021',
        ], $service->localizedDeliverables());

        $this->actingAs($this->superAdmin)
            ->withSession(['locale' => 'es'])
            ->get(route('admin.services.edit', $service))
            ->assertOk()
            ->assertSee('name="deliverables[0][content]" value="memoria descriptiva del proyecto"', false)
            ->assertSee('name="deliverables[1][content]" value="cálculos hidráulicos"', false)
            ->assertDontSee('name="deliverables[0][content]" value="project descriptive report"', false);
    }

    public function test_service_save_preserves_deliverable_ids_and_locale_pairing(): void
    {
        $service = $this->localService();
        $first = $service->deliverables()->create([
            'name' => 'First row',
            'name_en' => 'First row',
            'name_es' => 'Primera fila',
            'sort_order' => 1,
            'is_active' => true,
            'is_client_visible_by_default' => true,
        ]);
        $second = $service->deliverables()->create([
            'name' => 'Second row',
            'name_en' => 'Second row',
            'name_es' => 'Segunda fila',
            'sort_order' => 2,
            'is_active' => true,
            'is_client_visible_by_default' => true,
        ]);

        $this->fakeTranslator();

        $this->actingAs($this->superAdmin)
            ->put(route('admin.services.update', $service), $this->servicePayload([
                'content_locale' => 'en',
                'deliverables' => [
                    ['id' => $first->id, 'content' => 'First row updated', 'es' => 'Primera fila'],
                    ['id' => $second->id, 'content' => 'Second row', 'es' => 'Segunda fila'],
                    ['content' => 'Third row', 'es' => ''],
                ],
            ]))
            ->assertRedirect(route('admin.services.edit', $service));

        $service->refresh()->load('deliverables');
        $this->assertSame([$first->id, $second->id], $service->deliverables->take(2)->pluck('id')->all());
        $this->assertSame('First row updated', $first->fresh()->name_en);
        $this->assertSame('Primera fila', $first->fresh()->name_es);
        $this->assertSame('es: Third row', $service->deliverables->last()->name_es);

        $this->actingAs($this->superAdmin)
            ->withSession(['locale' => 'es'])
            ->put(route('admin.services.update', $service), $this->servicePayload([
                'content_locale' => 'es',
                'deliverables' => [
                    ['id' => $first->id, 'content' => 'Primera fila ajustada', 'en' => 'First row updated'],
                    ['id' => $service->deliverables->last()->id, 'content' => 'Tercera fila', 'en' => 'Third row'],
                ],
            ]))
            ->assertRedirect(route('admin.services.edit', $service));

        $service->refresh()->load('deliverables');
        $this->assertSame([$first->id, $service->deliverables->last()->id], $service->deliverables->pluck('id')->all());
        $this->assertModelMissing($second);
        $this->assertSame('Primera fila ajustada', $first->fresh()->name_es);
        $this->assertSame('First row updated', $first->fresh()->name_en);
        app()->setLocale('es');
        $this->assertSame(['Primera fila ajustada', 'Tercera fila'], $service->localizedDeliverables());
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
