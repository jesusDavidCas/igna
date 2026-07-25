<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Proposal;
use App\Models\User;
use App\Support\Proposals\ProposalContentSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProposalAdministrationUsabilityTest extends TestCase
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

    public function test_proposal_form_uses_phase_2b_section_order_and_no_general_documents(): void
    {
        $this->actingAs($this->superAdmin);
        $proposal = $this->createProposal('Editable proposal');

        foreach ([route('admin.proposals.create'), route('admin.proposals.edit', $proposal)] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSeeInOrder([
                    __('site.proposal_information'),
                    __('site.client_information'),
                    __('site.scope_and_deliverables'),
                    __('site.payment_schedule_and_totals'),
                    __('site.cost_items'),
                    __('site.signer_and_publication'),
                ])
                ->assertSee('data-proposal-section="identity"', false)
                ->assertSee('data-proposal-section="payments"', false)
                ->assertSee('data-proposal-section="costs"', false)
                ->assertDontSee('General proposal documents')
                ->assertDontSee('Documentos generales de la propuesta');
        }
    }

    public function test_proposal_index_uses_compact_created_at_arrow_sorting_safely(): void
    {
        $this->actingAs($this->superAdmin);

        $oldest = $this->createProposal('Old operations plan', now()->subDays(5));
        $newest = $this->createProposal('New operations plan', now()->subDay());

        $this->get(route('admin.proposals.index', ['search' => 'operations', 'status' => 'draft']))
            ->assertOk()
            ->assertSee(__('site.created_at'))
            ->assertSee('aria-label="'.__('site.sort_oldest_first').'"', false)
            ->assertSee('↓', false)
            ->assertSee('search=operations', false)
            ->assertSee('status=draft', false)
            ->assertSeeInOrder([$newest->proposal_number, $oldest->proposal_number])
            ->assertDontSee(__('site.newest_first'));

        $this->get(route('admin.proposals.index', ['sort' => 'created_at', 'direction' => 'asc']))
            ->assertOk()
            ->assertSee('aria-label="'.__('site.sort_newest_first').'"', false)
            ->assertSee('↑', false)
            ->assertSeeInOrder([$oldest->proposal_number, $newest->proposal_number]);

        $this->get(route('admin.proposals.index', ['sort' => 'unsafe_sql', 'direction' => 'drop table proposals']))
            ->assertOk()
            ->assertSeeInOrder([$newest->proposal_number, $oldest->proposal_number]);
    }

    public function test_general_proposal_documents_feature_is_removed(): void
    {
        $this->assertFalse(Route::has('admin.proposals.documents.store'));
        $this->assertFalse(Route::has('admin.proposals.documents.download'));
        $this->assertFalse(Route::has('admin.proposals.documents.destroy'));
        $this->assertFalse(class_exists('App\\Models\\ProposalDocument'));
        $this->assertFalse(Schema::hasTable('proposal_documents'));

        $proposal = $this->createProposal('PDF still available');

        $this->actingAs($this->superAdmin)
            ->get(route('admin.proposals.pdf', $proposal))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_rich_text_toolbar_controls_render_for_create_and_edit_with_independent_targets(): void
    {
        $this->actingAs($this->superAdmin);
        $proposal = $this->createProposal('Toolbar proposal');

        foreach ([route('admin.proposals.create'), route('admin.proposals.edit', $proposal)] as $url) {
            $html = $this->get($url)
                ->assertOk()
                ->assertSee('data-rich-text-field', false)
                ->assertSee('data-rich-command="bold"', false)
                ->assertSee('data-rich-command="italic"', false)
                ->assertSee('data-rich-command="insertUnorderedList"', false)
                ->assertSee('data-rich-command="insertOrderedList"', false)
                ->assertSee('data-rich-command="removeFormat"', false)
                ->assertSee('type="button" data-rich-command="bold"', false)
                ->assertSee('type="button" data-rich-command="italic"', false)
                ->assertSee('type="button" data-rich-command="insertUnorderedList"', false)
                ->assertSee('type="button" data-rich-command="insertOrderedList"', false)
                ->assertSee('type="button" data-rich-command="removeFormat"', false)
                ->getContent();

            $this->assertStringContainsString('data-rich-text-target="field-description"', $html);
            $this->assertStringContainsString('data-rich-text-target="field-scope"', $html);
            $this->assertSame(1, substr_count($html, 'id="field-description-editor"'));
            $this->assertSame(1, substr_count($html, 'id="field-scope-editor"'));
            $this->assertSame(1, substr_count($html, 'id="field-description-toolbar"'));
            $this->assertSame(1, substr_count($html, 'id="field-scope-toolbar"'));
        }
    }

    public function test_restricted_rich_text_is_sanitized_stored_and_rendered(): void
    {
        $this->actingAs($this->superAdmin);

        $this->post(route('admin.proposals.store'), $this->validPayload([
            'description' => '<p onclick="alert(1)"><b>Bold</b> <i>Italic</i><script>alert(1)</script><span style="color:red">safe text</span><a href="javascript:alert(1)">bad link</a></p>',
            'scope' => '<ul class="MsoList"><li data-x="1">Bullet</li></ul><ol><li style="color:red">Number</li></ol><iframe src="bad"></iframe>',
        ]))->assertRedirect();

        $proposal = Proposal::query()->where('title', 'Phase 2B valid proposal')->firstOrFail();

        $this->assertStringContainsString('<strong>Bold</strong>', $proposal->description);
        $this->assertStringContainsString('<em>Italic</em>', $proposal->description);
        $this->assertStringContainsString('safe text', $proposal->description);
        $this->assertStringContainsString('<ul><li>Bullet</li></ul>', $proposal->scope);
        $this->assertStringContainsString('<ol><li>Number</li></ol>', $proposal->scope);
        $this->assertStringNotContainsString('script', $proposal->description.$proposal->scope);
        $this->assertStringNotContainsString('onclick', $proposal->description.$proposal->scope);
        $this->assertStringNotContainsString('style=', $proposal->description.$proposal->scope);
        $this->assertSame($proposal->description, app(ProposalContentSanitizer::class)->clean($proposal->description));

        $this->get(route('admin.proposals.show', $proposal))
            ->assertOk()
            ->assertSee('<strong>Bold</strong>', false)
            ->assertSee('<em>Italic</em>', false)
            ->assertDontSee('onclick', false);

        $this->get($proposal->publicUrl())
            ->assertOk()
            ->assertSee('<ul><li>Bullet</li></ul>', false)
            ->assertDontSee('iframe', false);
    }

    public function test_rich_text_sanitizer_keeps_allowed_formats_independent_and_strips_unsafe_markup(): void
    {
        $sanitizer = app(ProposalContentSanitizer::class);

        $description = $sanitizer->clean('<p><strong>Bold</strong> <em>Italic</em></p><span style="font-size:99px">Plain</span><script>alert(1)</script>');
        $scope = $sanitizer->clean('<ul onclick="bad()"><li>Bullet</li></ul><ol><li style="color:red">Number</li></ol><img src=x onerror=alert(1)>');
        $cleared = $sanitizer->clean('Bold text'."\n".'Italic text'."\n".'Bullet text');

        $this->assertSame('<p><strong>Bold</strong> <em>Italic</em></p>Plain', $description);
        $this->assertSame('<ul><li>Bullet</li></ul><ol><li>Number</li></ol>', $scope);
        $this->assertSame('<p>Bold text<br>Italic text<br>Bullet text</p>', $cleared);
        $this->assertStringNotContainsString('script', $description.$scope);
        $this->assertStringNotContainsString('style=', $description.$scope);
        $this->assertStringNotContainsString('onclick', $description.$scope);
        $this->assertStringNotContainsString('<img', $description.$scope);
    }

    public function test_rich_text_old_input_and_template_append_controls_render(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this
            ->from(route('admin.proposals.create'))
            ->followingRedirects()
            ->post(route('admin.proposals.store'), [
                ...$this->validPayload(),
                'title' => '',
                'description' => '<p><strong>Retained rich description</strong></p>',
                'scope' => '<ol><li>Retained numbered scope</li></ol>',
                'items' => [
                    ['category' => 'Manual', 'item_code' => 'M-01', 'description' => 'Manually edited row', 'unit' => 'und', 'quantity' => '1', 'unit_value' => '100000'],
                    ['category' => 'Template', 'item_code' => 'T-01', 'description' => 'Appended template row retained', 'unit' => 'und', 'quantity' => '2', 'unit_value' => '200000'],
                ],
            ]);

        $response
            ->assertOk()
            ->assertSee('data-validation-summary', false)
            ->assertSee('data-rich-text-editor', false)
            ->assertSee('<strong>Retained rich description</strong>', false)
            ->assertSee('<ol><li>Retained numbered scope</li></ol>', false)
            ->assertSee('Manually edited row')
            ->assertSee('Appended template row retained')
            ->assertSee('data-proposal-template-copies', false)
            ->assertSee('data-add-template-items', false)
            ->assertSee(__('site.manage_service_templates'));
    }

    public function test_valid_proposal_creation_update_and_pdf_template_keep_complete_content(): void
    {
        $client = User::factory()->create(['role' => UserRole::CLIENT, 'is_active' => true]);
        $this->actingAs($this->superAdmin);
        $longDescription = str_repeat('Complete contractual item text ', 35);

        $this->post(route('admin.proposals.store'), $this->validPayload([
            'client_user_id' => $client->id,
            'description' => '<p>Summary</p><ul><li>Bullet scope detail</li></ul>',
            'scope' => '<p><strong>PDF bold</strong> <em>PDF italic</em></p><ul><li>PDF bullet</li></ul><ol><li>PDF number</li></ol>',
            'items' => [
                ['category' => 'General', 'item_code' => 'LONG-01', 'description' => $longDescription, 'unit' => 'und', 'quantity' => '1', 'unit_value' => '100000'],
            ],
        ]))->assertRedirect();

        $proposal = Proposal::query()->where('title', 'Phase 2B valid proposal')->firstOrFail();

        $this->put(route('admin.proposals.update', $proposal), $this->validPayload([
            'client_user_id' => $client->id,
            'title' => 'Phase 2B valid update',
            'scope' => '<p><strong>PDF bold</strong> <em>PDF italic</em></p><ul><li>PDF bullet</li></ul><ol><li>PDF number</li></ol>',
            'items' => [
                ['category' => 'Existing', 'item_code' => 'E-01', 'description' => 'Existing saved row survives edit', 'unit' => 'und', 'quantity' => '1', 'unit_value' => '100000'],
                ['category' => 'Appended', 'item_code' => 'A-01', 'description' => 'Appended row participates in totals', 'unit' => 'und', 'quantity' => '2', 'unit_value' => '50000'],
            ],
        ]))->assertRedirect(route('admin.proposals.show', $proposal));

        $proposal = $proposal->fresh('items');

        $this->assertSame('Phase 2B valid update', $proposal->title);
        $this->assertSame(2, $proposal->items()->count());
        $this->assertSame(200000.0, (float) $proposal->total);

        $html = view('admin.proposals.pdf', [
            'proposal' => $proposal,
            'brand' => ['company_name' => 'IGNA Studio', 'logo_text' => 'IG'],
            'proposalAccessUrl' => $proposal->publicUrl(),
            'qrCodeDataUri' => 'data:image/png;base64,'.base64_encode('qr'),
        ])->render();

        $this->assertStringContainsString('Existing saved row survives edit', $html);
        $this->assertStringContainsString('Appended row participates in totals', $html);
        $this->assertStringContainsString('<strong>PDF bold</strong>', $html);
        $this->assertStringContainsString('<em>PDF italic</em>', $html);
        $this->assertStringContainsString('<ul><li>PDF bullet</li></ul>', $html);
        $this->assertStringContainsString('<ol><li>PDF number</li></ol>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onclick=', $html);
        $this->assertStringNotContainsString('Str::limit', $html);
        $this->assertStringContainsString('signature-cell', $html);
        $this->assertStringContainsString('totals-cell', $html);
    }

    private function createProposal(string $title, mixed $createdAt = null): Proposal
    {
        $createdAt ??= now();

        return Proposal::query()->create([
            'proposal_number' => 'IGNA-2026-T'.str_pad((string) (Proposal::query()->count() + 1), 4, '0', STR_PAD_LEFT),
            'title' => $title,
            'subject' => 'Operational proposal',
            'description' => 'Description',
            'scope' => 'Scope',
            'timeline_months' => 1,
            'timeline_weeks' => 0,
            'timeline' => '1 month',
            'payment_plan' => 'Full payment - 100%',
            'payment_schedule' => [['label' => 'Full payment', 'percentage' => 100]],
            'status' => 'draft',
            'tax_rate' => 0,
            'subtotal' => 100000,
            'tax_total' => 0,
            'total' => 100000,
            'issued_at' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'validity_days' => 30,
            'created_by_user_id' => $this->superAdmin->id,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return [
            'title' => 'Phase 2B valid proposal',
            'subject' => 'Valid proposal subject',
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
                ['category' => 'General', 'item_code' => 'V-01', 'description' => 'Valid item', 'unit' => 'und', 'quantity' => '1', 'unit_value' => '100000'],
            ],
            ...$overrides,
        ];
    }
}
