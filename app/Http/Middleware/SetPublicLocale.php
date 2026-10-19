<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPublicLocale
{
    public function handle(Request $request, Closure $next, string $locale): Response
    {
        abort_unless(in_array($locale, ['es', 'en'], true), 404);

        // A localized public URL is authoritative; it must not inherit a session locale.
        app()->setLocale($locale);
        $request->attributes->set('public_locale', $locale);

        return $next($request);
    }
}
