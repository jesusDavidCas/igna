<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Proposal;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BulkProjectDeletionTest extends TestCase
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

    public function test_bulk_delete_routes_are_superadministrator_only_and_precede_ticket_binding(): void
    {
        $ticket = $this->ticket();
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $context = $this->selectionContext([$ticket->id]);

        $this->actingAs($admin)
            ->withSession($context)
            ->post(route('admin.tickets.bulk-delete.preview'), $this->selectionPayload($ticket->id, $this->contextToken($context)))
            ->assertForbidden();

        $this->actingAs($this->superAdmin)
            ->withSession($context)
            ->post(route('admin.tickets.bulk-delete.preview'), $this->selectionPayload($ticket->id, $this->contextToken($context)))
            ->assertOk()
            ->assertSee($ticket->ticket_code);
    }

    public function test_preview_rejects_empty_duplicate_oversized_and_cross_page_selections(): void
    {
        $tickets = collect(range(1, 51))->map(fn (): Ticket => $this->ticket());
        $context = $this->selectionContext([$tickets->first()->id]);

        $this->actingAs($this->superAdmin)
            ->withSession($context)
            ->post(route('admin.tickets.bulk-delete.preview'), [
                'selection_context' => $this->contextToken($context),
                'ticket_ids' => [],
            ])
            ->assertSessionHasErrors('ticket_ids');

        $oversizedContext = $this->selectionContext($tickets->pluck('id')->all());
        $this->actingAs($this->superAdmin)
            ->withSession($oversizedContext)
            ->post(route('admin.tickets.bulk-delete.preview'), [
                'selection_context' => $this->contextToken($oversizedContext),
                'ticket_ids' => $tickets->pluck('id')->all(),
            ])
            ->assertSessionHasErrors('ticket_ids');

        $this->actingAs($this->superAdmin)
            ->withSession($context)
            ->post(route('admin.tickets.bulk-delete.preview'), [
                'selection_context' => $this->contextToken($context),
                'ticket_ids' => [$tickets->first()->id, $tickets->last()->id],
            ])
            ->assertSessionHasErrors('ticket_ids');
    }

    public function test_preview_rejects_duplicate_and_protected_projects(): void
    {
        $ticket = $this->ticket();
        $context = $this->selectionContext([$ticket->id]);

        $this->actingAs($this->superAdmin)
            ->withSession($context)
            ->post(route('admin.tickets.bulk-delete.preview'), [
                'selection_context' => $this->contextToken($context),
                'ticket_ids' => [$ticket->id, $ticket->id],
            ])
            ->assertSessionHasErrors('ticket_ids.0');

        $proposal = $this->proposal();
        $protected = $this->ticket(['proposal_id' => $proposal->id]);
        $protectedContext = $this->selectionContext([$protected->id]);

        $this->actingAs($this->superAdmin)
            ->withSession($protectedContext)
            ->post(route('admin.tickets.bulk-delete.preview'), $this->selectionPayload($protected->id, $this->contextToken($protectedContext)))
            ->assertSessionHasErrors('ticket_ids');
    }

    public function test_confirmed_snapshot_deletes_only_the_previewed_unprocessed_projects_and_audits_each(): void
    {
        $first = $this->ticket(['ticket_code' => 'PRJ-BULK-ONE']);
        $second = $this->ticket(['ticket_code' => 'PRJ-BULK-TWO']);
        $context = $this->selectionContext([$first->id, $second->id]);

        $preview = $this->actingAs($this->superAdmin)
            ->withSession($context)
            ->post(route('admin.tickets.bulk-delete.preview'), [
                'selection_context' => $this->contextToken($context),
                'ticket_ids' => [$first->id, $second->id],
            ])
            ->assertOk()
            ->assertSee('PRJ-BULK-ONE')
            ->assertSee('PRJ-BULK-TWO');
        $token = $this->confirmationToken($preview->getContent());

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.tickets.bulk-delete.destroy'), [
                'confirmation_token' => $token,
                'confirm_deletion' => '1',
            ])
            ->assertRedirect(route('admin.tickets.index'));

        $this->assertDatabaseMissing('tickets', ['id' => $first->id]);
        $this->assertDatabaseMissing('tickets', ['id' => $second->id]);
        $this->assertDatabaseCount('deletion_audits', 2);
        $this->assertDatabaseHas('deletion_audits', ['entity_public_identifier' => 'PRJ-BULK-ONE']);
        $this->assertDatabaseHas('deletion_audits', ['entity_public_identifier' => 'PRJ-BULK-TWO']);
    }

    public function test_changed_or_replayed_confirmation_never_deletes_projects(): void
    {
        $ticket = $this->ticket();
        $context = $this->selectionContext([$ticket->id]);
        $preview = $this->actingAs($this->superAdmin)
            ->withSession($context)
            ->post(route('admin.tickets.bulk-delete.preview'), $this->selectionPayload($ticket->id, $this->contextToken($context)))
            ->assertOk();
        $token = $this->confirmationToken($preview->getContent());
        $ticket->update(['project_name' => 'Changed after preview']);

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.tickets.bulk-delete.destroy'), ['confirmation_token' => $token, 'confirm_deletion' => '1'])
            ->assertSessionHasErrors('confirmation_token');
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.tickets.bulk-delete.destroy'), ['confirmation_token' => $token, 'confirm_deletion' => '1'])
            ->assertSessionHasErrors('confirmation_token');
    }

    public function test_list_page_sizes_are_whitelisted_and_spanish_ui_renders(): void
    {
        collect(range(1, 16))->each(fn (): Ticket => $this->ticket());

        $this->actingAs($this->superAdmin)
            ->get(route('admin.tickets.index', ['per_page' => 30]))
            ->assertOk()
            ->assertViewHas('perPage', 30)
            ->assertSee(__('site.bulk_delete_review'));

        app()->setLocale('es');
        $this->actingAs($this->superAdmin)
            ->get(route('admin.tickets.index', ['per_page' => 999]))
            ->assertOk()
            ->assertViewHas('perPage', 15)
            ->assertSee('Revisar eliminación');
    }

    public function test_preview_requires_csrf_protection(): void
    {
        $ticket = $this->ticket();
        $context = $this->selectionContext([$ticket->id]);
        $this->app->instance('env', 'local');

        $this->withMiddleware(PreventRequestForgery::class)
            ->actingAs($this->superAdmin)
            ->withSession($context)
            ->call('POST', route('admin.tickets.bulk-delete.preview'), $this->selectionPayload($ticket->id, $this->contextToken($context)))
            ->assertStatus(419);
    }

    /** @return array<string, array<string, array<string, int|array<int, int>>>> */
    private function selectionContext(array $ticketIds): array
    {
        $token = Str::random(40);

        return ['bulk_ticket_delete.selection_contexts' => [$token => [
            'user_id' => $this->superAdmin->id,
            'expires_at' => now()->addMinutes(10)->getTimestamp(),
            'ticket_ids' => $ticketIds,
        ]]];
    }

    private function contextToken(array $context): string
    {
        return (string) array_key_first($context['bulk_ticket_delete.selection_contexts']);
    }

    private function selectionPayload(int $ticketId, ?string $token = null): array
    {
        return ['selection_context' => $token ?? Str::random(40), 'ticket_ids' => [$ticketId]];
    }

    private function confirmationToken(string $html): string
    {
        preg_match('/name="confirmation_token" value="([^"]+)"/', $html, $matches);
        $this->assertArrayHasKey(1, $matches);

        return $matches[1];
    }

    private function ticket(array $overrides = []): Ticket
    {
        $service = Service::query()->create([
            'name' => 'Service '.Str::random(8), 'name_en' => 'Service', 'name_es' => 'Servicio',
            'slug' => Str::lower(Str::random(12)), 'code' => Str::upper(Str::random(8)),
            'business_line' => 'digital', 'service_type' => 'web_platform', 'service_scope' => 'none',
            'description' => 'Synthetic service', 'description_en' => 'Synthetic service', 'description_es' => 'Servicio sintético',
            'deliverables_schema' => [], 'is_active' => true, 'sort_order' => 1,
        ]);
        $stage = $service->stages()->create(['name' => 'Discovery', 'code' => 'discovery', 'sort_order' => 1, 'is_active' => true, 'is_client_visible' => true]);

        return Ticket::query()->create([
            'ticket_code' => 'PRJ-'.Str::upper(Str::random(8)), 'service_id' => $service->id, 'current_service_stage_id' => $stage->id,
            'first_name' => 'Bulk', 'last_name' => 'Client', 'email' => Str::lower(Str::random(8)).'@example.com',
            'project_name' => 'Bulk deletion project', 'preferred_language' => 'en', 'project_description' => 'Synthetic project.',
            'status' => TicketStatus::NEW, 'submitted_at' => now(), ...$overrides,
        ]);
    }

    private function proposal(): Proposal
    {
        return Proposal::query()->create([
            'proposal_number' => 'PROP-'.Str::upper(Str::random(8)), 'title' => 'Proposal', 'title_en' => 'Proposal', 'title_es' => 'Propuesta',
            'subject' => 'Synthetic', 'status' => 'sent', 'tax_rate' => 0, 'subtotal' => 0, 'tax_total' => 0, 'total' => 0,
            'issued_at' => now()->toDateString(), 'valid_until' => now()->addDays(30)->toDateString(),
        ]);
    }
}
