<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user !== null, Response::HTTP_FORBIDDEN);

        $normalized = array_map(
            static fn (string $role): string => UserRole::from($role)->value,
            $roles,
        );

        abort_unless(in_array($user->role->value, $normalized, true), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
