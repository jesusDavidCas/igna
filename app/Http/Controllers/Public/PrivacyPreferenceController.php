<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\Privacy\ConsentPreferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

class PrivacyPreferenceController extends Controller
{
    public function store(Request $request, ConsentPreferences $preferences): RedirectResponse
    {
        abort_unless(config('privacy.optional_tracking_enabled'), 404);

        $allowedKeys = ['_token', 'action', ConsentPreferences::ANALYTICS, ConsentPreferences::ADVERTISING];
        abort_if(array_diff(array_keys($request->request->all()), $allowedKeys) !== [], 422);

        $validated = $request->validate([
            'action' => ['required', 'string', Rule::in(['save', 'reject'])],
            ConsentPreferences::ANALYTICS => ['nullable', 'boolean'],
            ConsentPreferences::ADVERTISING => ['nullable', 'boolean'],
        ]);

        $rejectOptional = $validated['action'] === 'reject';

        Cookie::queue(cookie(
            name: config('privacy.preferences_cookie'),
            value: json_encode($preferences->payload(
                ! $rejectOptional && (bool) ($validated[ConsentPreferences::ANALYTICS] ?? false),
                ! $rejectOptional && (bool) ($validated[ConsentPreferences::ADVERTISING] ?? false),
            ), JSON_THROW_ON_ERROR),
            minutes: config('privacy.preferences_lifetime_minutes'),
            path: '/',
            secure: app()->environment('production') || (bool) config('session.secure'),
            httpOnly: true,
            raw: false,
            sameSite: 'lax',
        ));

        return back()->with('success', __('site.privacy_preferences_saved'));
    }
}
