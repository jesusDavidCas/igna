<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTimelineTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $client;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed();

        $this->superAdmin = User::query()->where('role', UserRole::SUPER_ADMIN)->firstOrFail();
        $this->client = User::factory()->create(['role' => UserRole::CLIENT]);
        $this->service = Service::query()->firstOrFail();
    }

    public function test_project_index_displays_created_date_and_sorts_newest_first_by_default(): void
    {
        $oldest = $this->project('Oldest visible project', now()->subDays(7));
        $newest = $this->project('Newest visible project', now()->subDay());

        $this->actingAs($this->superAdmin)
            ->get(route('admin.tickets.index'))
            ->assertOk()
            ->assertSee(__('site.created_at'))
            ->assertSee('aria-label="'.__('site.sort_oldest_first').'"', false)
            ->assertSee('↓', false)
            ->assertSee($newest->created_at->translatedFormat('M j, Y'))
            ->assertSeeInOrder([$newest->ticket_code, $oldest->ticket_code]);
    }

    public function test_project_index_sorts_oldest_first_and_falls_back_from_unsafe_parameters(): void
    {
        $oldest = $this->project('Oldest sorted project', now()->subDays(7));
        $newest = $this->project('Newest sorted project', now()->subDay());

        $this->actingAs($this->superAdmin)
            ->get(route('admin.tickets.index', ['sort' => 'created_at', 'direction' => 'asc']))
            ->assertOk()
            ->assertSee('aria-label="'.__('site.sort_newest_first').'"', false)
            ->assertSee('↑', false)
            ->assertSeeInOrder([$oldest->ticket_code, $newest->ticket_code]);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.tickets.index', ['sort' => 'unsafe_sql', 'direction' => 'drop table tickets']))
            ->assertOk()
            ->assertSeeInOrder([$newest->ticket_code, $oldest->ticket_code]);
    }

    public function test_project_index_and_detail_use_spanish_creation_date_label(): void
    {
        $project = $this->project('Proyecto visible', now()->subDay());

        $this->actingAs($this->superAdmin)
            ->withSession(['locale' => 'es'])
            ->get(route('admin.tickets.index'))
            ->assertOk()
            ->assertSee(__('site.created_at'));

        $this->actingAs($this->superAdmin)
            ->withSession(['locale' => 'es'])
            ->get(route('admin.tickets.show', $project))
            ->assertOk()
            ->assertSee(__('site.created_at'))
            ->assertSee($project->created_at->translatedFormat('M j, Y'));
    }

    public function test_dashboard_uses_recent_projects_wording_dates_and_latest_order(): void
    {
        $oldest = $this->project('Older dashboard project', now()->subDays(7));
        $newest = $this->project('Newer dashboard project', now()->subDay());

        $this->actingAs($this->superAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('site.recent_requests'))
            ->assertDontSee('Recent requests')
            ->assertSee($newest->created_at->translatedFormat('M j, Y'))
            ->assertSeeInOrder([$newest->ticket_code, $oldest->ticket_code]);

        $this->actingAs($this->superAdmin)
            ->withSession(['locale' => 'es'])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Proyectos recientes')
            ->assertDontSee('Solicitudes recientes');
    }

    public function test_project_index_pagination_preserves_sort_query(): void
    {
        foreach (range(1, 16) as $index) {
            $this->project('Paginated project '.$index, now()->subDays($index));
        }

        $this->actingAs($this->superAdmin)
            ->get(route('admin.tickets.index', ['sort' => 'created_at', 'direction' => 'asc']))
            ->assertOk()
            ->assertSee('direction=asc', false);
    }

    private function project(string $name, mixed $createdAt): Ticket
    {
        return Ticket::query()->forceCreate([
            'ticket_code' => 'IGNA-PROJ-'.str_pad((string) (Ticket::query()->count() + 1), 4, '0', STR_PAD_LEFT),
            'service_id' => $this->service->id,
            'service_selection' => 'catalog',
            'service_public_category' => 'technology',
            'client_user_id' => $this->client->id,
            'first_name' => 'Local',
            'last_name' => 'Client',
            'email' => $this->client->email,
            'phone' => $this->client->phone,
            'project_name' => $name,
            'project_location' => 'Medellin',
            'preferred_language' => 'en',
            'project_description' => 'Project timeline test.',
            'target_date' => now()->addMonth()->toDateString(),
            'status' => TicketStatus::NEW,
            'submitted_at' => $createdAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
