<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Support\Seo\PublicContent;
use App\Support\Seo\SeoManager;
use Illuminate\Contracts\View\View;

class BlogController extends Controller
{
    public function index(SeoManager $seo, PublicContent $content): View
    {
        return $this->archive($seo, $content, false);
    }

    public function englishIndex(SeoManager $seo, PublicContent $content): View
    {
        abort_unless($content->englishBlogArchiveIsPublishable(), 404);

        return $this->archive($seo, $content, true);
    }

    private function archive(SeoManager $seo, PublicContent $content, bool $english): View
    {
        $posts = ($english ? $content->englishPublishedPostsQuery() : $content->publishedPostsQuery())
            ->latest('published_at')
            ->paginate(9);

        abort_if($posts->currentPage() > $posts->lastPage(), 404);

        $path = $english ? '/en/blog' : '/blog';
        $alternates = $content->englishBlogArchiveIsPublishable()
            ? $seo->paginatedAlternates('/blog', '/en/blog', $posts->currentPage())
            : [];

        return view('public.blog.index', [
            'posts' => $posts,
            'seo' => $seo->meta([
                'title' => __('site.seo_blog_title'),
                'description' => __('site.seo_blog_description'),
                'canonical' => $seo->paginatedCanonical($path, $posts->currentPage()),
                'alternates' => $alternates,
                'schema' => [
                    $seo->webpageSchema(__('site.seo_blog_title'), __('site.seo_blog_description'), $path),
                ],
            ]),
        ]);
    }

    public function show(BlogPost $post, SeoManager $seo, PublicContent $content): View
    {
        abort_unless($content->publishedPostsQuery()->whereKey($post->getKey())->exists(), 404);

        return $this->post($post, $seo, $content, '/blog/'.$post->slug, false);
    }

    public function englishShow(BlogPost $post, SeoManager $seo, PublicContent $content): View
    {
        abort_unless($content->isEnglishBlogPostPublishable($post), 404);

        return $this->post($post, $seo, $content, '/en/blog/'.$post->slug, true);
    }

    private function post(BlogPost $post, SeoManager $seo, PublicContent $content, string $path, bool $english): View
    {
        $title = $english ? $post->englishTitle() : $post->localizedTitle();
        $summary = $english ? $post->englishSummary() : $post->localizedSummary();
        $alternates = $content->isEnglishBlogPostPublishable($post)
            ? $seo->alternates('/blog/'.$post->slug, '/en/blog/'.$post->slug)
            : [];

        return view('public.blog.show', [
            'post' => $post,
            'englishContent' => $english,
            'seo' => $seo->meta([
                'title' => $title.' | IGNA Studio',
                'description' => $summary,
                'canonical' => $seo->canonicalUrl($path),
                'alternates' => $alternates,
                'type' => 'article',
                'image' => $post->headerImageUrl() ? url($post->headerImageUrl()) : $seo->socialImage(),
                'schema' => [
                    $seo->webpageSchema($title, $summary, $path),
                    $seo->blogPostingSchema($post, $path, $english),
                ],
            ]),
        ]);
    }
}
