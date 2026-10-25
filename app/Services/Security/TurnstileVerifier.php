<?php

namespace App\Services\Security;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TurnstileVerifier
{
    private const MAX_TOKEN_LENGTH = 2048;

    private const TEST_SITE_KEY = '1x00000000000000000000AA';

    private const TEST_SECRET_KEY = '1x0000000000000000000000000000000AA';

    public function verify(?string $token, ?string $remoteIp): TurnstileVerificationResult
    {
        $configuration = $this->configuration();

        if ($configuration === null) {
            return TurnstileVerificationResult::rejected('configuration_error');
        }

        if (! is_string($token) || $token === '' || mb_strlen($token) > self::MAX_TOKEN_LENGTH) {
            return TurnstileVerificationResult::rejected('invalid_token');
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout((int) config('services.turnstile.connect_timeout', 2))
                ->timeout((int) config('services.turnstile.timeout', 5))
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $configuration['secret'],
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ]);
        } catch (ConnectionException) {
            return TurnstileVerificationResult::unavailable('connection_failure');
        }

        if (! $response->successful()) {
            return TurnstileVerificationResult::unavailable('provider_http_failure');
        }

        $payload = $response->json();

        if (! is_array($payload) || ! array_key_exists('success', $payload) || ! is_bool($payload['success'])) {
            return TurnstileVerificationResult::unavailable('malformed_response');
        }

        if ($payload['success'] !== true) {
            return TurnstileVerificationResult::rejected('challenge_rejected');
        }

        if (($payload['hostname'] ?? null) !== $configuration['hostname']) {
            return TurnstileVerificationResult::rejected('hostname_mismatch');
        }

        if (($payload['action'] ?? null) !== $configuration['action']) {
            return TurnstileVerificationResult::rejected('action_mismatch');
        }

        $replayKey = 'turnstile:used:'.hash_hmac('sha256', $token, (string) config('app.key'));

        if (! Cache::add($replayKey, true, now()->addMinutes(10))) {
            return TurnstileVerificationResult::rejected('token_replayed');
        }

        return TurnstileVerificationResult::accepted();
    }

    /** @return array{secret:string, hostname:string, action:string}|null */
    private function configuration(): ?array
    {
        $siteKey = config('services.turnstile.site_key');
        $secret = config('services.turnstile.secret_key');
        $hostname = config('services.turnstile.expected_hostname');
        $action = config('services.turnstile.expected_action');

        if (! is_string($siteKey) || ! is_string($secret) || ! is_string($hostname) || ! is_string($action)
            || $siteKey === '' || $secret === '' || $hostname === '' || $action === '') {
            return null;
        }

        if (app()->environment('production') && (
            $hostname !== 'ignastudio.com'
            || $siteKey === self::TEST_SITE_KEY
            || $secret === self::TEST_SECRET_KEY
        )) {
            return null;
        }

        return compact('secret', 'hostname', 'action');
    }
}
