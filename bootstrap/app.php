<?php

use App\Http\Middleware\EnsureUserRole;
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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
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
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('site.upload_too_large', ['max' => '20 MB']),
                ], 413);
            }

            return response()->view('errors.post-too-large', [
                'message' => __('site.upload_too_large', ['max' => '20 MB']),
            ], 413);
        });
    })->create();
