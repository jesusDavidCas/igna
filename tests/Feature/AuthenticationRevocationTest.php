<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationRevocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_active_user_can_access_protected_routes(): void
    {
        $client = User::factory()->create([
            'role' => UserRole::CLIENT,
            'is_active' => true,
        ]);

        $this->actingAs($client)
            ->get(route('client.dashboard'))
            ->assertOk();
    }

    public function test_deactivation_terminates_existing_session_on_next_request_without_redirect_loop(): void
    {
        [$superAdmin, $client] = $this->users();
        $oldVersion = $client->auth_session_version;

        $this->actingAs($client)
            ->get(route('client.dashboard'))
            ->assertOk();

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $client), $this->userPayload($client, false))
            ->assertRedirect(route('admin.users.edit', $client));

        $client->refresh();

        $this->assertFalse($client->is_active);
        $this->assertGreaterThan($oldVersion, $client->auth_session_version);

        $this->be($client);
        $response = $this
            ->withSession([
                User::AUTH_SESSION_VERSION_KEY => $oldVersion,
                'locale' => 'es',
            ])
            ->get(route('client.dashboard'));

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => __('site.user_inactive', [], 'es'),
            ]);

        $this->assertGuest();
        $this->get(route('login'))->assertOk();
    }

    public function test_reactivation_does_not_restore_old_session_but_new_login_succeeds(): void
    {
        [$superAdmin, $client] = $this->users();
        $oldVersion = $client->auth_session_version;

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $client), $this->userPayload($client, false));

        $client->refresh();

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $client), $this->userPayload($client, true));

        $client->refresh();
        $this->assertTrue($client->is_active);

        $this->be($client);
        $this->withSession([User::AUTH_SESSION_VERSION_KEY => $oldVersion])
            ->get(route('client.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => __('site.auth_session_expired'),
            ]);

        Auth::forgetGuards();
        $this->app['session']->flush();

        $this->post(route('login.store'), [
            'email' => $client->email,
            'password' => 'password',
        ])->assertRedirect(route('client.dashboard'));
    }

    public function test_deactivation_rotates_remember_token_and_old_cookie_cannot_restore_access(): void
    {
        [$superAdmin, $client] = $this->users();

        $login = $this->post(route('login.store'), [
            'email' => $client->email,
            'password' => 'password',
        ])->assertRedirect(route('client.dashboard'));

        $guard = Auth::guard('web');
        $recallerName = $guard->getRecallerName();
        $oldRecaller = $login->getCookie($recallerName)?->getValue();
        $oldRememberToken = $client->fresh()->getRememberToken();

        $this->assertNotEmpty($oldRecaller);

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $client), $this->userPayload($client, false));

        $this->assertNotSame($oldRememberToken, $client->fresh()->getRememberToken());

        Auth::forgetGuards();
        $this->app['session']->flush();

        $this->withCookie($recallerName, (string) $oldRecaller)
            ->get(route('client.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_administrative_password_reset_revokes_old_session_and_remember_state(): void
    {
        [$superAdmin, $client] = $this->users();
        $oldVersion = $client->auth_session_version;

        $login = $this->post(route('login.store'), [
            'email' => $client->email,
            'password' => 'password',
        ]);

        $guard = Auth::guard('web');
        $recallerName = $guard->getRecallerName();
        $oldRecaller = $login->getCookie($recallerName)?->getValue();
        $oldRememberToken = $client->fresh()->getRememberToken();

        $this->actingAs($superAdmin)
            ->put(route('admin.users.password.update', $client), [
                'password' => 'NewClient123!',
                'password_confirmation' => 'NewClient123!',
            ])
            ->assertRedirect(route('admin.users.edit', $client));

        $client->refresh();

        $this->assertGreaterThan($oldVersion, $client->auth_session_version);
        $this->assertNotSame($oldRememberToken, $client->getRememberToken());
        $this->assertTrue(Hash::check('NewClient123!', $client->password));

        $this->actingAs($superAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->be($client);
        $this->withSession([User::AUTH_SESSION_VERSION_KEY => $oldVersion])
            ->get(route('client.dashboard'))
            ->assertRedirect(route('login'));

        Auth::forgetGuards();
        $this->app['session']->flush();

        $this->withCookie($recallerName, (string) $oldRecaller)
            ->get(route('client.dashboard'))
            ->assertRedirect(route('login'));

        Auth::forgetGuards();
        $this->app['session']->flush();

        $this->post(route('login.store'), [
            'email' => $client->email,
            'password' => 'NewClient123!',
        ])->assertRedirect(route('client.dashboard'));
    }

    public function test_revoking_one_user_does_not_terminate_an_unaffected_user(): void
    {
        $target = User::factory()->create();
        $unaffected = User::factory()->create([
            'role' => UserRole::CLIENT,
        ]);

        $target->revokeAuthenticationSessions();

        $this->actingAs($unaffected)
            ->get(route('client.dashboard'))
            ->assertOk();
    }

    private function users(): array
    {
        return [
            User::factory()->create([
                'role' => UserRole::SUPER_ADMIN,
                'is_active' => true,
            ]),
            User::factory()->create([
                'role' => UserRole::CLIENT,
                'is_active' => true,
                'password' => Hash::make('password'),
            ]),
        ];
    }

    private function userPayload(User $user, bool $active): array
    {
        return [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'preferred_language' => $user->preferred_language,
            'role' => $user->role->value,
            'is_active' => $active ? '1' : '0',
            'password' => null,
        ];
    }
}
