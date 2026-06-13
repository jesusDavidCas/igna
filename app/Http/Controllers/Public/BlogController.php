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
        return view('public.blog.index', [
            'posts' => $content->publishedPostsQuery()
                ->latest('published_at')
                ->paginate(9),
            'seo' => $seo->meta([
                'title' => __('site.seo_blog_title'),
                'description' => __('site.seo_blog_description'),
                'canonical' => $seo->canonicalUrl('/blog'),
                'schema' => [
                    $seo->webpageSchema(__('site.seo_blog_title'), __('site.seo_blog_description'), '/blog'),
                ],
            ]),
        ]);
    }

    public function show(BlogPost $post, SeoManager $seo, PublicContent $content): View
    {
        abort_unless($content->publishedPostsQuery()->whereKey($post->getKey())->exists(), 404);

        return view('public.blog.show', [
            'post' => $post,
            'seo' => $seo->meta([
                'title' => $post->localizedTitle().' | IGNA Studio',
                'description' => $post->localizedSummary(),
                'canonical' => $seo->canonicalUrl('/blog/'.$post->slug),
                'type' => 'article',
                'image' => $post->headerImageUrl() ? url($post->headerImageUrl()) : $seo->socialImage(),
                'schema' => [
                    $seo->webpageSchema($post->localizedTitle(), $post->localizedSummary(), '/blog/'.$post->slug),
                    $seo->blogPostingSchema($post),
                ],
            ]),
        ]);
    }
}
