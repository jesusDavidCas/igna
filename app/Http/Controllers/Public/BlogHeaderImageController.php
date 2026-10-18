<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Services\Blog\BlogHeaderImageManager;
use App\Support\Seo\PublicContent;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlogHeaderImageController extends Controller
{
    public function __invoke(Request $request, BlogPost $post, BlogHeaderImageManager $images, PublicContent $content): Response
    {
        abort_unless($content->publishedPostsQuery()->whereKey($post->getKey())->exists(), 404);

        $file = $images->publicFileFor($post);

        abort_unless($file, 404);

        $isCurrentVersion = hash_equals($post->headerImageVersion(), (string) $request->query('v'));

        $headers = [
            'Content-Type' => $file['mime_type'],
            'Cache-Control' => $isCurrentVersion
                ? 'public, max-age=31536000, immutable'
                : 'no-cache',
            'ETag' => $file['etag'],
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($request->headers->get('If-None-Match') === $file['etag']) {
            return response('', 304, $headers);
        }

        return response($file['contents'], 200, [
            ...$headers,
            'Content-Length' => (string) strlen($file['contents']),
        ]);
    }
}
