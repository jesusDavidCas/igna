<?php

namespace Tests\Feature;

use App\Support\Privacy\ConsentPreferences;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PrivacyPreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed();
    }

    public function test_optional_preference_interface_is_dormant_without_approved_optional_tracking(): void
    {
        config()->set('privacy.optional_tracking_enabled', false);

        $this->get('/')
            ->assertOk()
            ->assertDontSee(__('site.privacy_preferences_heading'));

        $this->post(route('privacy.preferences.store'))
            ->assertNotFound();
    }

    public function test_optional_preferences_are_not_preselected_and_are_saved_only_after_an_explicit_choice(): void
    {
        config()->set('privacy.optional_tracking_enabled', true);

        $this->get('/')
            ->assertOk()
            ->assertSee(__('site.privacy_preferences_heading'))
            ->assertDontSee('checked', false);

        $this->from('/')
            ->post(route('privacy.preferences.store'), [
                'action' => 'save',
                ConsentPreferences::ANALYTICS => true,
            ])
            ->assertRedirect('/')
            ->assertCookie(config('privacy.preferences_cookie'));
    }

    public function test_optional_categories_default_to_denied_and_require_the_current_preference_version(): void
    {
        $preferences = app(ConsentPreferences::class);

        $this->assertFalse($preferences->allows(Request::create('/'), ConsentPreferences::ANALYTICS));

        $request = Request::create('/', 'GET', [], [
            config('privacy.preferences_cookie') => json_encode([
                'version' => config('privacy.preferences_version'),
                'expires_at' => now()->addMinute()->getTimestamp(),
                ConsentPreferences::ANALYTICS => true,
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->assertTrue($preferences->allows($request, ConsentPreferences::ANALYTICS));
        $this->assertFalse($preferences->allows($request, ConsentPreferences::ADVERTISING));
    }

    public function test_malformed_outdated_expired_and_truthy_cookie_values_fail_closed(): void
    {
        $preferences = app(ConsentPreferences::class);

        $invalidValues = [
            'not-json',
            json_encode([
                'version' => config('privacy.preferences_version') - 1,
                'expires_at' => now()->addMinute()->getTimestamp(),
                ConsentPreferences::ANALYTICS => true,
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'version' => config('privacy.preferences_version'),
                'expires_at' => now()->subSecond()->getTimestamp(),
                ConsentPreferences::ANALYTICS => true,
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'version' => config('privacy.preferences_version'),
                'expires_at' => now()->addMinute()->getTimestamp(),
                ConsentPreferences::ANALYTICS => 'true',
                ConsentPreferences::ADVERTISING => 1,
            ], JSON_THROW_ON_ERROR),
        ];

        foreach ($invalidValues as $value) {
            $request = Request::create('/', 'GET', [], [
                config('privacy.preferences_cookie') => $value,
            ]);

            $this->assertFalse($preferences->allows($request, ConsentPreferences::ANALYTICS));
            $this->assertFalse($preferences->allows($request, ConsentPreferences::ADVERTISING));
        }
    }

    public function test_reject_action_persists_denied_optional_categories(): void
    {
        config()->set('privacy.optional_tracking_enabled', true);

        $response = $this->from('/')
            ->post(route('privacy.preferences.store'), [
                'action' => 'reject',
                ConsentPreferences::ANALYTICS => true,
                ConsentPreferences::ADVERTISING => true,
            ])
            ->assertRedirect('/');

        $response->assertCookie(config('privacy.preferences_cookie'));

        $payload = app(ConsentPreferences::class)->payload(false, false);

        $this->assertFalse($payload[ConsentPreferences::ANALYTICS]);
        $this->assertFalse($payload[ConsentPreferences::ADVERTISING]);
    }

    public function test_invalid_actions_and_unsupported_categories_are_rejected(): void
    {
        config()->set('privacy.optional_tracking_enabled', true);

        $this->postJson(route('privacy.preferences.store'), ['action' => 'allow-all'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('action');

        $this->postJson(route('privacy.preferences.store'), [
            'action' => 'save',
            'unknown_category' => true,
        ])->assertUnprocessable();
    }
}
