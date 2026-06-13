<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Seo\SeoManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    public function create(SeoManager $seo): View
    {
        return view('auth.forgot-password', [
            'seo' => $seo->meta([
                'title' => __('site.seo_forgot_password_title'),
                'description' => __('site.forgot_password_intro'),
                'canonical' => $seo->canonicalUrl('/forgot-password'),
                'robots' => 'noindex, nofollow',
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($validated);

        return back()->with('status', __('site.password_reset_link_sent'));
    }
}
