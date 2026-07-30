<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureActiveAuthenticatedSession;
use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\RedirectCanonicalHost;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        App\Console\Commands\TranslateMissingContent::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            RedirectCanonicalHost::class,
            SetLocale::class,
            AddSecurityHeaders::class,
            EnsureActiveAuthenticatedSession::class,
        ]);

        $middleware->redirectUsersTo(function (Request $request): string {
            $user = $request->user();

            return $user?->canAccessAdmin()
                ? route('admin.dashboard')
                : route('client.dashboard');
        });

        $middleware->alias([
            'role' => EnsureUserRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            $routeName = $request->route()?->getName();
            $max = in_array($routeName, ['requests.store', 'client.tickets.documents.store', 'tracking.documents.store'], true)
                ? '2 MB'
                : '20 MB';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('site.upload_too_large', ['max' => $max]),
                ], 413);
            }

            return response()->view('errors.post-too-large', [
                'message' => __('site.upload_too_large', ['max' => $max]),
            ], 413);
        });
    })->create();
