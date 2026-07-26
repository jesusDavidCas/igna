<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAuthenticatedSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $sessionVersion = (int) $request->session()->get(User::AUTH_SESSION_VERSION_KEY, 0);
        $currentVersion = (int) $user->auth_session_version;

        if ($user->is_active && $sessionVersion === $currentVersion) {
            return $next($request);
        }

        $message = $user->is_active
            ? __('site.auth_session_expired')
            : __('site.user_inactive');

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_UNAUTHORIZED);
        }

        return redirect()
            ->route('login')
            ->withErrors(['email' => $message]);
    }
}
