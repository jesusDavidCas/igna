<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Services\Blog\BlogHeaderImageManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlogHeaderImageController extends Controller
{
    public function __invoke(Request $request, BlogPost $post, BlogHeaderImageManager $images): Response
    {
        $file = $images->publicFileFor($post);

        abort_unless($file, 404);

        $headers = [
            'Content-Type' => $file['mime_type'],
            'Cache-Control' => 'public, max-age=604800',
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
