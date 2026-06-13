<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Support\Seo\PublicContent;
use App\Support\Seo\SeoManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class SeoResourceController extends Controller
{
    public function sitemap(SeoManager $seo, PublicContent $content): Response
    {
        $urls = collect([
            ['loc' => $seo->canonicalUrl('/'), 'lastmod' => null],
            ['loc' => $seo->canonicalUrl('/blog'), 'lastmod' => null],
        ]);

        $urls = $urls
            ->merge($content->teamMembers()->map(fn ($member): array => [
                'loc' => $seo->canonicalUrl('/team/'.$member->slug),
                'lastmod' => optional($member->updated_at)->toDateString(),
            ]))
            ->merge($content->publishedPosts()->map(fn (BlogPost $post): array => [
                'loc' => $seo->canonicalUrl('/blog/'.$post->slug),
                'lastmod' => optional($post->updated_at ?? $post->published_at)->toDateString(),
            ]));

        $xml = view('seo.sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(SeoManager $seo): Response
    {
        $text = "User-agent: *\nDisallow:\n\nSitemap: ".$seo->canonicalUrl('/sitemap.xml')."\n";

        return response($text, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function llms(SeoManager $seo, PublicContent $content): Response
    {
        $markdown = view('seo.llms', [
            'seo' => $seo,
            'services' => $content->services(),
            'teamMembers' => $content->teamMembers(),
            'posts' => $content->publishedPosts(),
        ])->render();

        return response($markdown, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function llmsAlias(): RedirectResponse
    {
        return redirect('/llms.txt', 301);
    }

    public function markdown(string $page, SeoManager $seo, PublicContent $content): Response
    {
        abort_unless(in_array($page, ['home', 'services', 'team'], true), 404);

        $view = match ($page) {
            'services' => 'seo.markdown.services',
            'team' => 'seo.markdown.team',
            default => 'seo.markdown.home',
        };

        return $this->markdownResponse(view($view, [
            'seo' => $seo,
            'services' => $content->services(),
            'teamMembers' => $content->teamMembers(),
            'posts' => $content->publishedPosts()->take(5),
        ])->render());
    }

    public function teamMarkdown(string $slug, SeoManager $seo, PublicContent $content): Response
    {
        $member = $content->teamMembers()->firstWhere('slug', $slug);

        abort_if($member === null, 404);

        return $this->markdownResponse(view('seo.markdown.team-member', [
            'seo' => $seo,
            'member' => $member,
        ])->render());
    }

    public function blogMarkdown(BlogPost $post, SeoManager $seo, PublicContent $content): Response
    {
        abort_unless($content->publishedPostsQuery()->whereKey($post->getKey())->exists(), 404);

        return $this->markdownResponse(view('seo.markdown.blog-post', [
            'seo' => $seo,
            'post' => $post,
            'bodyText' => $content->stripHtmlToText($post->localizedBodyHtml()),
        ])->render());
    }

    private function markdownResponse(string $markdown): Response
    {
        return response(trim($markdown)."\n", 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'X-Robots-Tag' => 'noindex, follow',
        ]);
    }
}
