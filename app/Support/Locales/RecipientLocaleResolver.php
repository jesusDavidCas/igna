<?php

namespace App\Support\Locales;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\File;

class RecipientLocaleResolver
{
    public function forTicketClient(Ticket $ticket): string
    {
        $ticket->loadMissing('client');

        return $this->normalize(
            $ticket->preferred_language,
            $ticket->client?->preferred_language,
            $this->applicationFallback(),
        );
    }

    public function forAdmin(?User $admin = null): string
    {
        return $this->normalize(
            $admin?->preferred_language,
            $this->adminDefault(),
            $this->applicationFallback(),
        );
    }

    public function adminDefault(): string
    {
        return $this->normalize(config('app.locale'), config('app.fallback_locale'), 'en');
    }

    public function applicationFallback(): string
    {
        return $this->normalize(config('app.fallback_locale'), config('app.locale'), 'en');
    }

    public function normalize(?string ...$candidates): string
    {
        $supported = $this->supportedLocales();

        foreach ($candidates as $candidate) {
            $locale = strtolower(trim((string) $candidate));

            if (in_array($locale, $supported, true)) {
                return $locale;
            }
        }

        return in_array('en', $supported, true) ? 'en' : ($supported[0] ?? 'en');
    }

    private function supportedLocales(): array
    {
        return collect(File::directories(lang_path()))
            ->map(fn (string $path): string => basename($path))
            ->filter(fn (string $locale): bool => preg_match('/^[a-z]{2}$/', $locale) === 1)
            ->values()
            ->all();
    }
}
