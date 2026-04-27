<?php

namespace App\Providers;

use App\Support\Settings\BrandSettings;
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
        View::composer(['layouts.public', 'layouts.panel'], function ($view): void {
            $view->with('brandSettings', app(BrandSettings::class)->publicPayload());
        });

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
