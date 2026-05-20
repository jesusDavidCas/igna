<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Service;
use App\Models\TeamMember;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        return view('public.home', [
            'services' => Service::query()
                ->with(['stages' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'posts' => BlogPost::query()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->latest('published_at')
                ->limit(3)
                ->get(),
            'teamProfiles' => TeamMember::query()
                ->where('is_active', true)
                ->with('publicCredentials')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function team(string $slug): View
    {
        $profile = TeamMember::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with('publicCredentials')
            ->first();

        abort_if($profile === null, 404);

        return view('public.team.show', [
            'profile' => $profile,
        ]);
    }

    public function locale(Request $request, string $locale)
    {
        abort_unless(in_array($locale, ['es', 'en'], true), 404);

        $request->session()->put('locale', $locale);

        return back();
    }
}
