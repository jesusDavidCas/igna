<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\Seo\SeoManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function create(SeoManager $seo): View
    {
        return view('auth.login', [
            'seo' => $seo->meta([
                'title' => __('site.nav_login').' | IGNA Studio',
                'description' => __('site.login_intro'),
                'canonical' => $seo->canonicalUrl('/login'),
                'robots' => 'noindex, nofollow',
            ]),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::attempt($credentials, remember: true)) {
            return back()
                ->withInput($request->safe()->except('password'))
                ->withErrors([
                    'email' => __('auth.failed'),
                ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        if (! $user->is_active) {
            Auth::logout();

            return back()->withErrors([
                'email' => __('site.user_inactive'),
            ]);
        }

        if ($user->canAccessAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('client.dashboard');
    }

    public function destroy(): RedirectResponse
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
