<?php

namespace App\Providers;

use App\Support\Settings\BrandSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('public-service-request', function (Request $request): array {
            $client = hash_hmac('sha256', $request->ip(), (string) config('app.key'));
            $response = function () use ($client) {
                Log::notice('request_antispam.rate_limited', ['client' => $client]);

                return response(__('site.request_antispam_rate_limited'), 429);
            };

            return [
                Limit::perMinute((int) config('services.turnstile.requests_per_minute', 5))
                    ->by($client)
                    ->response($response),
                Limit::perHour((int) config('services.turnstile.requests_per_hour', 20))
                    ->by($client)
                    ->response($response),
            ];
        });

        RateLimiter::for('ticket-document-upload', function (Request $request): Limit {
            $ticket = $request->route('ticket');
            $ticketKey = is_object($ticket) && method_exists($ticket, 'getKey') ? $ticket->getKey() : (string) $ticket;
            $context = (string) $request->query('email_hash', 'missing-context');

            return Limit::perMinutes(15, 5)
                ->by($ticketKey.'|'.$context.'|'.$request->ip())
                ->response(fn () => response(__('site.too_many_upload_attempts'), 429));
        });

        View::composer(['layouts.public', 'layouts.panel'], function ($view): void {
            $view->with('brandSettings', app(BrandSettings::class)->publicPayload());
        });

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
