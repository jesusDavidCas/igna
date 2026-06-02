<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PasswordResetNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_login_page_links_to_password_reset_request(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'), false)
            ->assertSee(__('site.forgot_password'));
    }

    public function test_existing_user_can_request_a_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), [
            'email' => $user->email,
        ])->assertSessionHas('status', __('site.password_reset_link_sent'));

        Notification::assertSentTo($user, PasswordResetNotification::class);
    }

    public function test_unknown_email_gets_generic_reset_response_without_notification(): void
    {
        Notification::fake();

        $this->post(route('password.email'), [
            'email' => 'unknown@example.com',
        ])->assertSessionHas('status', __('site.password_reset_link_sent'));

        Notification::assertNothingSent();
    }

    public function test_user_can_reset_password_and_login_with_new_password(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'password' => Hash::make('OldIgna12345!'),
        ]);

        $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $token = null;

        Notification::assertSentTo($user, PasswordResetNotification::class, function (PasswordResetNotification $notification) use (&$token): bool {
            $token = $notification->token;

            return ! empty($token);
        });

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewIgna12345!',
            'password_confirmation' => 'NewIgna12345!',
        ])->assertRedirect(route('login'));

        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'NewIgna12345!',
        ])->assertRedirect(route('client.dashboard'));
    }
}
