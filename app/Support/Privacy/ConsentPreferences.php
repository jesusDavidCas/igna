<?php

namespace App\Support\Privacy;

use Illuminate\Http\Request;

class ConsentPreferences
{
    public const ANALYTICS = 'analytics';

    public const ADVERTISING = 'advertising';

    public function fromRequest(Request $request): array
    {
        $preferences = json_decode((string) $request->cookie(config('privacy.preferences_cookie')), true);

        if (! is_array($preferences)
            || ($preferences['version'] ?? null) !== config('privacy.preferences_version')
            || ! is_int($preferences['expires_at'] ?? null)
            || $preferences['expires_at'] < now()->getTimestamp()) {
            return $this->defaults();
        }

        return [
            'analytics' => ($preferences[self::ANALYTICS] ?? false) === true,
            'advertising' => ($preferences[self::ADVERTISING] ?? false) === true,
        ];
    }

    public function allows(Request $request, string $category): bool
    {
        return $this->fromRequest($request)[$category] ?? false;
    }

    public function payload(bool $analytics, bool $advertising): array
    {
        return [
            'version' => config('privacy.preferences_version'),
            'expires_at' => now()->addMinutes(config('privacy.preferences_lifetime_minutes'))->getTimestamp(),
            self::ANALYTICS => $analytics,
            self::ADVERTISING => $advertising,
        ];
    }

    private function defaults(): array
    {
        return [
            self::ANALYTICS => false,
            self::ADVERTISING => false,
        ];
    }
}
