<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Proposal;
use App\Models\ProposalServiceTemplate;
use App\Models\Service;
use App\Models\User;
use App\Services\Services\ServiceContentTranslator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalCostTemplateCatalogueTest extends TestCase
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

    public function test_admins_can_manage_catalogue_and_clients_cannot(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);

        $this->get(route('admin.proposal-templates.index'))
            ->assertRedirect(route('login'));

        $this->actingAs($client)
            ->get(route('admin.proposal-templates.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.proposal-templates.index'))
            ->assertOk()
            ->assertSee(__('site.admin_proposal_templates'));

        $this->actingAs($this->superAdmin)
            ->get(route('admin.proposal-templates.create'))
            ->assertOk()
            ->assertSee(__('site.proposal_template_title'))
            ->assertDontSee(__('site.proposal_template_title_en'))
            ->assertDontSee(__('site.proposal_template_title_es'))
            ->assertDontSee(__('site.business_line_digital'));
    }

    public function test_catalogue_stores_titles_status_order_and_cost_rows(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.proposal-templates.store'), $this->templatePayload([
                'code' => 'strategy-pack',
                'service_number' => '42',
                'sort_order' => '7',
                'is_active' => '0',
                'items' => [
                    ['item_code' => 'DISC', 'description_en' => 'Discovery workshop', 'description_es' => 'Taller de descubrimiento', 'unit' => 'session', 'quantity' => '2', 'unit_value' => '1500.50'],
                    ['item_code' => '', 'description_en' => '', 'description_es' => '', 'unit' => '', 'quantity' => '', 'unit_value' => ''],
                    ['item_code' => 'ROAD', 'description_en' => 'Roadmap', 'description_es' => 'Hoja de ruta', 'unit' => 'doc', 'quantity' => '1', 'unit_value' => '900'],
                ],
            ]))
            ->assertRedirect();

        $template = ProposalServiceTemplate::query()->where('code', 'STRATEGY-PACK')->firstOrFail();

        $this->assertFalse($template->is_active);
        $this->assertSame(42, $template->service_number);
        $this->assertSame(7, $template->sort_order);
        $this->assertSame('Strategy package', $template->name_en);
        $this->assertSame('Paquete estratégico', $template->name_es);
        $this->assertSame('Strategy package', $template->landing_title_en);
        $this->assertSame(2, $template->items()->count());
        $this->assertDatabaseHas('proposal_service_template_items', [
            'proposal_service_template_id' => $template->id,
            'item_code' => 'DISC',
            'description_en' => 'Discovery workshop',
            'description_es' => 'Taller de descubrimiento',
            'quantity' => 2,
            'unit_value' => 1500.50,
            'sort_order' => 1,
        ]);
    }

    public function test_catalogue_is_clean_and_template_form_uses_one_visible_title_field(): void
    {
        $template = $this->createTemplate('CLEAN-CAT', 'Clean template', isActive: true);

        $html = $this->actingAs($this->superAdmin)
            ->get(route('admin.proposal-templates.index'))
            ->assertOk()
            ->assertSee('CLEAN-CAT')
            ->assertSee('Clean template')
            ->assertSee(__('site.edit'))
            ->assertSee(__('site.duplicate_template'))
            ->assertSee(__('site.delete'))
            ->assertSee(__('site.confirm_delete_template_title'))
            ->assertDontSee(__('site.deactivate_template'))
            ->assertDontSee(__('site.activate_template'))
            ->assertDontSee(__('site.proposal_template_item_count', ['count' => $template->items()->count()]))
            ->assertDontSee(__('site.proposal_template_sort_order').': '.$template->sort_order)
            ->assertDontSee(str_pad((string) $template->service_number, 2, '0', STR_PAD_LEFT).' · Clean template')
            ->getContent();

        $this->assertStringNotContainsString('Site edit', $html);
        $this->assertStringNotContainsString('Side edit', $html);
        $this->assertStringNotContainsString('Edit site', $html);
        $this->assertStringNotContainsString('site.edit', $html);

        $formHtml = $this->withSession(['locale' => 'en'])
            ->get(route('admin.proposal-templates.edit', $template))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="items[0][description]"', false)
            ->assertSee(__('site.template_row_description'))
            ->assertDontSee(__('site.proposal_template_title_en'))
            ->assertDontSee(__('site.proposal_template_title_es'))
            ->assertDontSee(__('site.template_row_en'))
            ->assertDontSee(__('site.template_row_es'))
            ->getContent();

        $this->assertSame(0, substr_count($formHtml, 'id="template-name-en"'));
        $this->assertSame(0, substr_count($formHtml, 'id="template-name-es"'));
        $this->assertSame(1, substr_count($formHtml, 'name="items[0][description]"'));
        $this->assertSame(0, substr_count($formHtml, 'name="items[0][description_en]"'));
    }

    public function test_template_row_visible_description_switches_with_locale_when_translation_cache_exists(): void
    {
        $this->fakeTranslator();

        $this->actingAs($this->superAdmin)
            ->withSession(['locale' => 'en'])
            ->post(route('admin.proposal-templates.store'), $this->templatePayload([
                'name' => 'Localized template',
                'name_en' => '',
                'name_es' => '',
                'items' => [
                    ['item_code' => 'LOC', 'description' => 'English cost row', 'description_es' => '', 'unit' => 'hr', 'quantity' => '1', 'unit_value' => '250'],
                ],
            ]))
            ->assertRedirect();

        $template = ProposalServiceTemplate::query()->where('code', 'STRATEGY')->firstOrFail();
        $item = $template->items()->firstOrFail();

        $this->assertSame('English cost row', $item->description_en);
        $this->assertSame('es: English cost row', $item->description_es);

        $this->actingAs($this->superAdmin)
            ->withSession(['locale' => 'es'])
            ->get(route('admin.proposal-templates.edit', $template))
            ->assertOk()
            ->assertSee('name="items[0][description]"', false)
            ->assertSee('es: English cost row')
            ->assertDontSee('English row description')
            ->assertDontSee('Spanish row description');

        $this->actingAs($this->superAdmin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.proposal-templates.edit', $template))
            ->assertOk()
            ->assertSee('English cost row')
            ->assertDontSee('es: English cost row</textarea>', false);
    }

    public function test_template_title_cache_does_not_accept_copied_source_as_translation(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.proposal-templates.store'), $this->templatePayload([
                'name' => 'Single template title',
                'name_es' => 'Single template title',
            ]))
            ->assertRedirect()
            ->assertSessionHas('warning', __('site.dynamic_translation_unavailable'));

        $template = ProposalServiceTemplate::query()->where('code', 'STRATEGY')->firstOrFail();

        $this->assertSame('Single template title', $template->name_en);
        $this->assertSame('', $template->name_es);
    }

    public function test_validation_retains_submitted_titles_and_rows(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->from(route('admin.proposal-templates.create'))
            ->followingRedirects()
            ->post(route('admin.proposal-templates.store'), $this->templatePayload([
                'code' => '',
                'name_en' => 'Retained English title',
                'name_es' => 'Título retenido',
                'items' => [
                    ['item_code' => 'KEEP', 'description_en' => 'Retained row', 'description_es' => '', 'unit' => 'hr', 'quantity' => '1', 'unit_value' => '250'],
                ],
            ]));

        $response
            ->assertOk()
            ->assertSee('data-proposal-template-form', false)
            ->assertSee('Retained English title')
            ->assertSee('Título retenido')
            ->assertSee('Retained row')
            ->assertSee('KEEP');
    }

    public function test_legacy_inactive_templates_remain_visible_and_usable_until_deleted(): void
    {
        $active = $this->createTemplate('ACTIVE-CAT', 'Active catalogue template', isActive: true);
        $inactive = $this->createTemplate('INACTIVE-CAT', 'Inactive catalogue template', isActive: false);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.proposal-templates.index'))
            ->assertOk()
            ->assertSee('Active catalogue template')
            ->assertSee('Inactive catalogue template')
            ->assertSee(__('site.legacy_inactive_template'))
            ->assertSee(__('site.delete'));

        $this->actingAs($this->superAdmin)
            ->get(route('admin.proposals.create'))
            ->assertOk()
            ->assertSee('Active catalogue template')
            ->assertSee('Inactive catalogue template')
            ->assertSee('<option value="'.$active->id.'">', false)
            ->assertSee('<option value="'.$inactive->id.'">', false);
    }

    public function test_duplicate_creates_active_editable_copy_without_mutating_source(): void
    {
        $source = $this->createTemplate('DUP-CAT', 'Duplicate source', isActive: true);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.proposal-templates.duplicate', $source))
            ->assertRedirect();

        $copy = ProposalServiceTemplate::query()
            ->where('code', 'DUP-CAT-COPY')
            ->firstOrFail();

        $this->assertTrue($copy->is_active);
        $this->assertSame('Duplicate source Copy', $copy->name_en);
        $this->assertSame($source->items()->count(), $copy->items()->count());
        $this->assertNotSame($source->items()->firstOrFail()->id, $copy->items()->firstOrFail()->id);
        $this->assertTrue($source->fresh()->is_active);

        $this->put(route('admin.proposal-templates.update', $copy), $this->templatePayload([
            'code' => $copy->code,
            'name_en' => 'Independent copy',
            'name_es' => 'Copia independiente',
            'is_active' => '1',
        ]))->assertRedirect(route('admin.proposal-templates.edit', $copy));

        $this->assertSame('Duplicate source', $source->fresh()->name_en);
        $this->assertSame('Independent copy', $copy->fresh()->name_en);
        $this->assertTrue($copy->fresh()->is_active);
    }

    public function test_authorized_template_delete_removes_rows_and_preserves_historical_proposal_snapshots(): void
    {
        $template = $this->createTemplate('DELETE-CAT', 'Delete source', isActive: true);
        $other = $this->createTemplate('KEEP-CAT', 'Keep source', isActive: true);
        $item = $template->items()->firstOrFail();
        $service = Service::query()->firstOrFail();
        $ticketCount = $service->tickets()->count();

        $this->actingAs($this->superAdmin)
            ->post(route('admin.proposals.store'), $this->proposalPayload([
                'items' => [
                    [
                        'category' => '',
                        'item_code' => $item->item_code,
                        'description' => $item->description_en,
                        'unit' => $item->unit,
                        'quantity' => (string) $item->quantity,
                        'unit_value' => (string) $item->unit_value,
                    ],
                ],
            ]))
            ->assertRedirect();

        $proposal = Proposal::query()->where('title', 'Catalogue proposal')->firstOrFail();
        $savedItem = $proposal->items()->firstOrFail();

        $this->delete(route('admin.proposal-templates.destroy', $template))
            ->assertRedirect(route('admin.proposal-templates.index'))
            ->assertSessionHas('success', __('site.proposal_template_deleted'));

        $this->assertModelMissing($template);
        $this->assertDatabaseMissing('proposal_service_template_items', [
            'proposal_service_template_id' => $template->id,
        ]);
        $this->assertModelExists($other->fresh());
        $this->assertSame(1, $other->items()->count());
        $this->assertModelExists($proposal);
        $this->assertSame('Planning row', $savedItem->fresh()->description);
        $this->assertSame(5000.0, (float) $savedItem->fresh()->unit_value);
        $this->assertSame($ticketCount, $service->fresh()->tickets()->count());

        $this->get(route('admin.proposals.create'))
            ->assertOk()
            ->assertDontSee('<option value="'.$template->id.'">', false)
            ->assertSee('<option value="'.$other->id.'">', false);

        $this->delete(route('admin.proposal-templates.destroy', $template))
            ->assertNotFound();
    }

    public function test_template_delete_is_authorized_and_not_available_to_guests_clients_or_public_methods(): void
    {
        $template = $this->createTemplate('AUTH-DELETE', 'Delete authorization', isActive: true);
        $client = User::factory()->create(['role' => UserRole::CLIENT]);

        auth()->logout();
        $this->flushSession();

        $this->delete(route('admin.proposal-templates.destroy', $template))
            ->assertRedirect(route('login'));

        $this->actingAs($client)
            ->delete(route('admin.proposal-templates.destroy', $template))
            ->assertForbidden();

        $this->actingAs($this->superAdmin)
            ->get('/admin/proposal-templates/'.$template->id)
            ->assertMethodNotAllowed();

        $this->assertModelExists($template);
    }

    public function test_template_rows_insert_as_saved_proposal_items_and_remain_historical_snapshots(): void
    {
        $template = $this->createTemplate('SNAP-CAT', 'Snapshot template', isActive: true);
        $item = $template->items()->firstOrFail();

        $this->actingAs($this->superAdmin)
            ->post(route('admin.proposals.store'), $this->proposalPayload([
                'items' => [
                    [
                        'category' => '',
                        'item_code' => $item->item_code,
                        'description' => $item->description_en,
                        'unit' => $item->unit,
                        'quantity' => (string) $item->quantity,
                        'unit_value' => (string) $item->unit_value,
                    ],
                ],
            ]))
            ->assertRedirect();

        $proposal = Proposal::query()->where('title', 'Catalogue proposal')->firstOrFail();
        $savedItem = $proposal->items()->firstOrFail();

        $template->update(['name_en' => 'Mutated template', 'is_active' => false]);
        $item->update(['description_en' => 'Mutated source row', 'unit_value' => 999999]);

        $this->assertSame('Planning row', $savedItem->fresh()->description);
        $this->assertSame(5000.0, (float) $savedItem->fresh()->unit_value);

        $html = view('admin.proposals.pdf', [
            'proposal' => $proposal->fresh('items'),
            'brand' => ['company_name' => 'IGNA Studio', 'logo_text' => 'IG'],
            'proposalAccessUrl' => $proposal->publicUrl(),
            'qrCodeDataUri' => 'data:image/png;base64,'.base64_encode('qr'),
        ])->render();

        $this->assertStringContainsString('Planning row', $html);
        $this->assertStringNotContainsString('Mutated source row', $html);
    }

    public function test_public_service_catalogue_remains_independent_from_proposal_templates(): void
    {
        $service = Service::query()->create([
            'name' => 'Public engineering service',
            'name_en' => 'Public engineering service',
            'name_es' => 'Servicio público de ingeniería',
            'slug' => 'public-engineering-service',
            'code' => 'PES-5A5',
            'business_line' => 'engineering',
            'service_type' => 'hydrology',
            'service_scope' => 'study',
            'description' => 'Public service description.',
            'description_en' => 'Public service description.',
            'description_es' => 'Descripción pública del servicio.',
            'deliverables_schema' => ['Technical brief'],
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $template = $this->createTemplate('PUBLIC-INDEPENDENT', 'Cost-only template', isActive: true);

        $this->actingAs($this->superAdmin)
            ->put(route('admin.proposal-templates.update', $template), $this->templatePayload([
                'code' => $template->code,
                'name_en' => 'Updated cost-only template',
                'name_es' => 'Plantilla solo de costos actualizada',
                'items' => [
                    ['item_code' => 'UPD', 'description_en' => 'Updated row', 'description_es' => 'Fila actualizada', 'unit' => 'doc', 'quantity' => '1', 'unit_value' => '100'],
                ],
            ]))
            ->assertRedirect(route('admin.proposal-templates.edit', $template));

        $this->assertSame('Public engineering service', $service->fresh()->name_en);

        $service->update(['name_en' => 'Public service renamed']);

        $this->assertSame('Updated cost-only template', $template->fresh()->name_en);
    }

    private function createTemplate(string $code, string $nameEn, bool $isActive): ProposalServiceTemplate
    {
        $template = ProposalServiceTemplate::query()->create([
            'code' => $code,
            'service_number' => ((int) (ProposalServiceTemplate::query()->max('service_number') ?? 0)) + 1,
            'name_en' => $nameEn,
            'name_es' => $nameEn.' ES',
            'landing_title_en' => $nameEn,
            'landing_title_es' => $nameEn.' ES',
            'landing_description_en' => null,
            'landing_description_es' => null,
            'is_active' => $isActive,
            'sort_order' => ((int) (ProposalServiceTemplate::query()->max('sort_order') ?? 0)) + 1,
        ]);

        $template->items()->create([
            'item_code' => 'PLAN',
            'description_en' => 'Planning row',
            'description_es' => 'Fila de planeación',
            'unit' => 'phase',
            'quantity' => 1,
            'unit_value' => 5000,
            'sort_order' => 1,
        ]);

        return $template;
    }

    private function templatePayload(array $overrides = []): array
    {
        return [
            'code' => 'STRATEGY',
            'service_number' => '10',
            'name_en' => 'Strategy package',
            'name_es' => 'Paquete estratégico',
            'sort_order' => '10',
            'is_active' => '1',
            'items' => [
                ['item_code' => 'STR-01', 'description_en' => 'Strategy row', 'description_es' => 'Fila estratégica', 'unit' => 'doc', 'quantity' => '1', 'unit_value' => '1000'],
            ],
            ...$overrides,
        ];
    }

    private function proposalPayload(array $overrides = []): array
    {
        return [
            'title' => 'Catalogue proposal',
            'subject' => 'Catalogue proposal subject',
            'description' => '<p>Valid proposal description.</p>',
            'scope' => '<p>Valid proposal scope.</p>',
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
                ['category' => '', 'item_code' => 'V-01', 'description' => 'Valid item', 'unit' => 'und', 'quantity' => '1', 'unit_value' => '100000'],
            ],
            ...$overrides,
        ];
    }

    private function fakeTranslator(): void
    {
        app()->instance(ServiceContentTranslator::class, new class extends ServiceContentTranslator
        {
            public function translate(?string $value, string $sourceLocale, string $targetLocale): string
            {
                return $targetLocale.': '.trim((string) $value);
            }
        });
    }
}
