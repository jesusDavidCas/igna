<?php

namespace App\Providers;

use App\Support\Settings\BrandSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
