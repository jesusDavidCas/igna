<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Service;
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
            'teamProfiles' => $this->teamProfiles(),
        ]);
    }

    public function team(string $slug): View
    {
        $profile = collect($this->teamProfiles())->firstWhere('slug', $slug);

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

    private function teamProfiles(): array
    {
        return [
            [
                'slug' => 'jesus-david-castaneda',
                'name' => 'Jesús David Castañeda',
                'role' => __('site.team_jesus_role'),
                'summary' => __('site.team_jesus_summary'),
                'bio' => [
                    __('site.team_jesus_bio_1'),
                    __('site.team_jesus_bio_2'),
                ],
                'expertise' => [
                    __('site.team_jesus_expertise_1'),
                    __('site.team_jesus_expertise_2'),
                    __('site.team_jesus_expertise_3'),
                    __('site.team_jesus_expertise_4'),
                ],
            ],
            [
                'slug' => 'roberto-castaneda-pardo',
                'name' => 'Roberto Castañeda Pardo',
                'role' => __('site.team_roberto_role'),
                'summary' => __('site.team_roberto_summary'),
                'bio' => [
                    __('site.team_roberto_bio_1'),
                    __('site.team_roberto_bio_2'),
                ],
                'expertise' => [
                    __('site.team_roberto_expertise_1'),
                    __('site.team_roberto_expertise_2'),
                    __('site.team_roberto_expertise_3'),
                    __('site.team_roberto_expertise_4'),
                ],
            ],
        ];
    }
}
