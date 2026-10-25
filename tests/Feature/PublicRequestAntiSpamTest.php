<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Ticket;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicRequestAntiSpamTest extends TestCase
{
    use RefreshDatabase;

    private int $initialTicketCount;

    private int $initialStageEventCount;

    private int $initialFileCount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed();
        Mail::fake();
        Cache::flush();
        Http::preventStrayRequests();
        $this->initialTicketCount = Ticket::query()->count();
        $this->initialStageEventCount = DB::table('ticket_stage_events')->count();
        $this->initialFileCount = DB::table('ticket_files')->count();
        config()->set('services.turnstile', [
            'enabled' => true,
            'site_key' => 'site-key-for-isolated-tests',
            'secret_key' => 'secret-key-for-isolated-tests',
            'expected_hostname' => 'ignastudio.com',
            'expected_action' => 'service_request',
            'connect_timeout' => 2,
            'timeout' => 5,
            'requests_per_minute' => 5,
            'requests_per_hour' => 20,
        ]);
    }

    public function test_successful_verification_creates_one_ticket_and_posts_only_to_turnstile(): void
    {
        $this->fakeSuccessfulTurnstile();

        $this->post(route('requests.store'), $this->payload())
            ->assertRedirect(route('tracking.index'));

        $this->assertDatabaseCount('tickets', $this->initialTicketCount + 1);
        Http::assertSent(function (ClientRequest $request): bool {
            return $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
                && $request['secret'] === 'secret-key-for-isolated-tests'
                && $request['response'] === 'valid-turnstile-token'
                && $request['remoteip'] === '127.0.0.1';
        });
    }

    public function test_missing_or_oversized_tokens_are_rejected_before_persistence(): void
    {
        $this->post(route('requests.store'), $this->payload(['cf-turnstile-response' => '']))
            ->assertSessionHasErrors('request');
        $this->post(route('requests.store'), $this->payload(['cf-turnstile-response' => str_repeat('x', 2049)]))
            ->assertSessionHasErrors('cf-turnstile-response');

        $this->assertRejectedWithoutSideEffects();
        Http::assertNothingSent();
    }

    public function test_invalid_hostname_action_and_replayed_tokens_are_rejected(): void
    {
        Http::fakeSequence()
            ->push(['success' => true, 'hostname' => 'invalid.example', 'action' => 'service_request'])
            ->push(['success' => true, 'hostname' => 'ignastudio.com', 'action' => 'wrong_action'])
            ->push(['success' => true, 'hostname' => 'ignastudio.com', 'action' => 'service_request'])
            ->push(['success' => true, 'hostname' => 'ignastudio.com', 'action' => 'service_request']);

        $this->post(route('requests.store'), $this->payload(['cf-turnstile-response' => 'hostname-token']))
            ->assertSessionHasErrors('request');
        $this->post(route('requests.store'), $this->payload(['cf-turnstile-response' => 'action-token']))
            ->assertSessionHasErrors('request');
        $this->post(route('requests.store'), $this->payload(['cf-turnstile-response' => 'replay-token']))
            ->assertRedirect(route('tracking.index'));
        $this->post(route('requests.store'), $this->payload(['cf-turnstile-response' => 'replay-token']))
            ->assertSessionHasErrors('request');

        $this->assertDatabaseCount('tickets', $this->initialTicketCount + 1);
    }

    public function test_invalid_challenge_honeypot_and_missing_configuration_create_no_side_effects(): void
    {
        Http::fake(['https://challenges.cloudflare.com/*' => Http::response(['success' => false], 200)]);

        $this->post(route('requests.store'), $this->payload())
            ->assertSessionHasErrors('request');
        $this->post(route('requests.store'), $this->payload(['website' => 'https://bot.example']))
            ->assertSessionHasErrors('request');

        config()->set('services.turnstile.secret_key', null);
        $this->post(route('requests.store'), $this->payload(['cf-turnstile-response' => 'configuration-token']))
            ->assertSessionHasErrors('request');

        $this->assertRejectedWithoutSideEffects();
    }

    public function test_production_rejects_cloudflare_test_credentials_without_calling_the_provider(): void
    {
        config()->set('services.turnstile.site_key', '1x00000000000000000000AA');
        config()->set('services.turnstile.secret_key', '1x0000000000000000000000000000000AA');
        $this->app->detectEnvironment(fn (): string => 'production');

        try {
            $this->post(route('requests.store'), $this->payload())
                ->assertSessionHasErrors('request');
        } finally {
            $this->app->detectEnvironment(fn (): string => 'testing');
        }

        $this->assertRejectedWithoutSideEffects();
        Http::assertNothingSent();
    }

    public function test_production_requires_the_ignastudio_hostname_without_calling_the_provider(): void
    {
        config()->set('services.turnstile.expected_hostname', 'preview.ignastudio.com');
        $this->app->detectEnvironment(fn (): string => 'production');

        try {
            $this->post(route('requests.store'), $this->payload())
                ->assertSessionHasErrors('request');
        } finally {
            $this->app->detectEnvironment(fn (): string => 'testing');
        }

        $this->assertRejectedWithoutSideEffects();
        Http::assertNothingSent();
    }

    public function test_provider_failures_are_safe_and_tokens_are_not_flashed(): void
    {
        Http::fake(['https://challenges.cloudflare.com/*' => Http::response('<invalid>', 200)]);

        $this->from('/')
            ->post(route('requests.store'), $this->payload(['cf-turnstile-response' => 'sensitive-token']))
            ->assertRedirect('/')
            ->assertSessionHasErrors('request')
            ->assertSessionMissing('_old_input.cf-turnstile-response');

        $this->assertRejectedWithoutSideEffects();

        Cache::flush();
        Http::fake(['https://challenges.cloudflare.com/*' => Http::failedConnection()]);
        $this->post(route('requests.store'), $this->payload(['cf-turnstile-response' => 'network-token']))
            ->assertSessionHasErrors('request');

        Cache::flush();
        Http::fake(['https://challenges.cloudflare.com/*' => Http::response([], 503)]);
        $this->post(route('requests.store'), $this->payload(['cf-turnstile-response' => 'http-token']))
            ->assertSessionHasErrors('request');

        $this->assertRejectedWithoutSideEffects();
    }

    public function test_named_rate_limit_returns_429_and_disabled_enforcement_preserves_existing_requests(): void
    {
        config()->set('services.turnstile.enabled', false);
        config()->set('services.turnstile.requests_per_minute', 2);
        config()->set('services.turnstile.requests_per_hour', 20);

        $this->post(route('requests.store'), $this->payload(['project_name' => 'First request']))->assertRedirect();
        $this->post(route('requests.store'), $this->payload(['project_name' => 'Second request']))->assertRedirect();
        $this->post(route('requests.store'), $this->payload(['project_name' => 'Third request']))
            ->assertStatus(429)
            ->assertSee(__('site.request_antispam_rate_limited'));

        $this->assertDatabaseCount('tickets', $this->initialTicketCount + 2);
    }

    public function test_successful_verification_preserves_the_other_service_request_flow(): void
    {
        $this->fakeSuccessfulTurnstile();

        $this->post(route('requests.store'), $this->payload([
            'service_id' => 'other',
            'project_name' => 'Other protected request',
        ]))->assertRedirect(route('tracking.index'));

        $this->assertDatabaseHas('tickets', [
            'project_name' => 'Other protected request',
            'service_id' => null,
            'service_selection' => 'other',
        ]);
    }

    public function test_csrf_protection_precedes_the_anti_spam_guard(): void
    {
        $this->app->instance('env', 'local');

        $this->withMiddleware(PreventRequestForgery::class)
            ->call('POST', route('requests.store'), $this->payload())
            ->assertStatus(419);

        $this->assertRejectedWithoutSideEffects();
        Http::assertNothingSent();
    }

    public function test_form_renders_the_widget_without_exposing_the_secret_in_spanish_and_english(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('class="cf-turnstile"', false)
            ->assertSee('data-sitekey="site-key-for-isolated-tests"', false)
            ->assertSee('challenges.cloudflare.com/turnstile/v0/api.js', false)
            ->assertSee(__('site.request_antispam_challenge_help'))
            ->assertDontSee('secret-key-for-isolated-tests');

        $this->assertSame(
            'Complete the security check before sending your request.',
            __('site.request_antispam_challenge_help', [], 'en'),
        );
    }

    private function fakeSuccessfulTurnstile(): void
    {
        Http::fake(['https://challenges.cloudflare.com/*' => Http::response([
            'success' => true,
            'hostname' => 'ignastudio.com',
            'action' => 'service_request',
        ], 200)]);
    }

    private function assertRejectedWithoutSideEffects(): void
    {
        $this->assertDatabaseCount('tickets', $this->initialTicketCount);
        $this->assertDatabaseCount('ticket_stage_events', $this->initialStageEventCount);
        $this->assertDatabaseCount('ticket_files', $this->initialFileCount);
        Mail::assertNothingSent();
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'first_name' => 'Public',
            'last_name' => 'Client',
            'email' => 'public.client@example.com',
            'phone' => '+57 300 123 4567',
            'project_name' => 'Protected request',
            'project_location' => 'Bogota',
            'preferred_language' => 'es',
            'service_id' => (string) Service::query()->where('is_active', true)->firstOrFail()->id,
            'project_description' => 'Synthetic request used only in the isolated feature test suite.',
            'target_date' => now()->addWeeks(2)->toDateString(),
            'website' => '',
            'cf-turnstile-response' => 'valid-turnstile-token',
        ], $overrides);
    }
}
