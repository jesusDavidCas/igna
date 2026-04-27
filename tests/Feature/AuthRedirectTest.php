<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed();
    }

    public function test_super_admin_login_redirects_to_admin_area(): void
    {
        $this->post(route('login.store'), [
            'email' => 'admin@ignastudio.com',
            'password' => 'Igna12345!',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_authenticated_admin_visiting_login_goes_to_admin_area(): void
    {
        $admin = User::query()->where('email', 'admin@ignastudio.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_authenticated_client_visiting_login_goes_to_my_services(): void
    {
        $client = User::factory()->create([
            'role' => UserRole::CLIENT,
        ]);

        $this->actingAs($client)
            ->get(route('login'))
            ->assertRedirect(route('client.dashboard'));
    }
}
