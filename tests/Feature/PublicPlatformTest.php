<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed();
    }

    public function test_public_pages_are_available(): void
    {
        $this->get('/')->assertOk();
        $this->get('/tracking')->assertOk();
        $this->get('/blog')->assertOk();
        $this->get('/login')->assertOk();
    }

    public function test_locale_switch_changes_public_copy(): void
    {
        $this->withSession(['locale' => 'es'])
            ->get('/')
            ->assertOk()
            ->assertSee('Cuéntanos sobre el proyecto que necesitas avanzar.')
            ->assertDontSee('brief');

        $this->post(route('locale.switch', 'en'))->assertRedirect();

        $this->get('/')
            ->assertOk()
            ->assertSee('Tell us about the project you need to move forward.')
            ->assertSee('Login');
    }

    public function test_public_request_creates_a_ticket_with_workflow(): void
    {
        $service = Service::query()->firstOrFail();

        $response = $this->post(route('requests.store'), [
            'first_name' => 'Jesus',
            'last_name' => 'Castaneda',
            'email' => 'client@example.com',
            'phone' => '+57 300 123 4567',
            'project_name' => 'Proyecto Base',
            'project_location' => 'Bogota',
            'preferred_language' => 'es',
            'service_id' => $service->id,
            'project_description' => 'Proyecto de prueba para validar el flujo.',
            'target_date' => now()->addWeeks(2)->toDateString(),
        ]);

        $response->assertRedirect(route('tracking.index'));

        $ticket = Ticket::query()
            ->with('stageEvents')
            ->where('project_name', 'Proyecto Base')
            ->firstOrFail();

        $this->assertStringStartsWith('IGNA-', $ticket->ticket_code);
        $this->assertSame($service->id, $ticket->service_id);
        $this->assertCount($service->stages()->count(), $ticket->stageEvents);
    }

    public function test_public_request_rejects_inactive_services(): void
    {
        $service = Service::query()->firstOrFail();
        $service->update(['is_active' => false]);

        $this->post(route('requests.store'), [
            'first_name' => 'Jesus',
            'last_name' => 'Castaneda',
            'email' => 'client@example.com',
            'phone' => '+57 300 123 4567',
            'project_name' => 'Proyecto Base',
            'project_location' => 'Bogota',
            'preferred_language' => 'es',
            'service_id' => $service->id,
            'project_description' => 'Proyecto de prueba para validar el flujo.',
            'target_date' => now()->addWeeks(2)->toDateString(),
        ])->assertSessionHasErrors('service_id');
    }
}
