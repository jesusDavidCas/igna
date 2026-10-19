<?php

namespace App\Support\Seo;

use Illuminate\Http\Request;

class PublicLocaleUrls
{
    public function home(): string
    {
        return route($this->isEnglish() ? 'en.home' : 'home');
    }

    public function blogIndex(): string
    {
        return route($this->isEnglish() ? 'en.blog.index' : 'blog.index');
    }

    public function blogShow(string $slug): string
    {
        return route($this->isEnglish() ? 'en.blog.show' : 'blog.show', $slug);
    }

    public function teamShow(string $slug): string
    {
        return route($this->isEnglish() ? 'en.team.show' : 'team.show', $slug);
    }

    /** @return array{url: string, label: string}|null */
    public function switchFor(Request $request, PublicContent $content): ?array
    {
        $routeName = (string) $request->route()?->getName();

        if (str_starts_with($routeName, 'en.')) {
            return match ($routeName) {
                'en.home' => ['url' => route('home'), 'label' => 'ES'],
                'en.blog.index' => ['url' => route('blog.index'), 'label' => 'ES'],
                'en.blog.show' => ['url' => route('blog.show', $request->route('post')), 'label' => 'ES'],
                'en.team.show' => ['url' => route('team.show', $request->route('slug')), 'label' => 'ES'],
                default => null,
            };
        }

        return match ($routeName) {
            'home' => $content->englishHomepageIsPublishable()
                ? ['url' => route('en.home'), 'label' => 'EN']
                : null,
            'blog.index' => $content->englishBlogArchiveIsPublishable()
                ? ['url' => route('en.blog.index'), 'label' => 'EN']
                : null,
            'blog.show' => $content->isEnglishBlogPostPublishable($request->route('post'))
                ? ['url' => route('en.blog.show', $request->route('post')), 'label' => 'EN']
                : null,
            'team.show' => $content->isEnglishTeamMemberPublishable($request->route('slug'))
                ? ['url' => route('en.team.show', $request->route('slug')), 'label' => 'EN']
                : null,
            default => null,
        };
    }

    public function isEnglish(): bool
    {
        return request()->routeIs('en.*');
    }
}
