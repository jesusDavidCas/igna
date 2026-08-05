<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\Settings\BrandSettings;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BrandFaviconController extends Controller
{
    public function __invoke(Request $request, BrandSettings $brandSettings): Response
    {
        $file = $brandSettings->configuredFaviconFile() ?? $brandSettings->fallbackFaviconFile();
        $headers = [
            'Content-Type' => $file['mime_type'],
            'Cache-Control' => 'public, max-age=604800',
            'ETag' => $file['etag'],
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($request->headers->get('If-None-Match') === $file['etag']) {
            return response('', 304, $headers);
        }

        return response($file['contents'], 200, $headers);
    }
}
