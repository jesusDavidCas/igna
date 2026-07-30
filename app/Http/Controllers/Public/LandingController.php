<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Service;
use App\Services\Services\PublicServiceTaxonomy;
use App\Models\TeamMember;
use App\Support\Seo\PublicContent;
use App\Support\Seo\SeoManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function __invoke(SeoManager $seo, PublicContent $content, PublicServiceTaxonomy $taxonomy): View
    {
        $services = Service::query()
            ->with(['stages' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('public.home', [
            'services' => $services,
            'requestServiceGroups' => $taxonomy->groupServices($services),
            'posts' => $content->publishedPostsQuery()
                ->latest('published_at')
                ->limit(3)
                ->get(),
            'teamProfiles' => TeamMember::query()
                ->where('is_active', true)
                ->with('publicCredentials')
                ->orderBy('sort_order')
                ->get(),
            'seo' => $seo->meta([
                'title' => config('igna.seo.default_title'),
                'description' => __('site.seo_home_description'),
                'canonical' => $seo->canonicalUrl('/'),
                'schema' => [
                    $seo->organizationSchema(),
                    $seo->websiteSchema(),
                    $seo->webpageSchema(config('igna.seo.default_title'), __('site.seo_home_description'), '/'),
                ],
            ]),
        ]);
    }

    public function team(string $slug, SeoManager $seo): View
    {
        $profile = TeamMember::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with('publicCredentials')
            ->first();

        abort_if($profile === null, 404);

        return view('public.team.show', [
            'profile' => $profile,
            'seo' => $seo->meta([
                'title' => $profile->name.' | IGNA Studio',
                'description' => $profile->short_description,
                'canonical' => $seo->canonicalUrl('/team/'.$profile->slug),
                'type' => 'profile',
                'schema' => [
                    $seo->webpageSchema($profile->name, $profile->short_description, '/team/'.$profile->slug),
                    $seo->personSchema($profile),
                ],
            ]),
        ]);
    }

    public function locale(Request $request, string $locale)
    {
        abort_unless(in_array($locale, ['es', 'en'], true), 404);

        $request->session()->put('locale', $locale);

        return back();
    }
}
