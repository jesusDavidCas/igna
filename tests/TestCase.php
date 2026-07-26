<?php

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private const TEST_CSRF_TOKEN = 'testing-csrf-token';

    public function actingAs(Authenticatable $user, $guard = null)
    {
        parent::actingAs($user, $guard);

        if ($user instanceof User) {
            $this->withSession([
                User::AUTH_SESSION_VERSION_KEY => $user->auth_session_version,
            ]);
        }

        return $this;
    }

    public function post($uri, array $data = [], array $headers = [])
    {
        return $this->csrfRequest('POST', $uri, $data, $headers);
    }

    public function put($uri, array $data = [], array $headers = [])
    {
        return $this->csrfRequest('PUT', $uri, $data, $headers);
    }

    public function patch($uri, array $data = [], array $headers = [])
    {
        return $this->csrfRequest('PATCH', $uri, $data, $headers);
    }

    public function delete($uri, array $data = [], array $headers = [])
    {
        return $this->csrfRequest('DELETE', $uri, $data, $headers);
    }

    private function csrfRequest(string $method, string $uri, array $data = [], array $headers = [])
    {
        $headers = ['X-CSRF-TOKEN' => self::TEST_CSRF_TOKEN] + $headers;
        $data = ['_token' => self::TEST_CSRF_TOKEN] + $data;

        return $this
            ->withSession(['_token' => self::TEST_CSRF_TOKEN])
            ->call($method, $uri, $data, [], [], $this->transformHeadersToServerVars($headers));
    }
}
