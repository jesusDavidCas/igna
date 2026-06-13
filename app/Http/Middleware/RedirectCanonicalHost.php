<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $canonical = parse_url(config('igna.seo.canonical_url', config('app.url')));
        $canonicalHost = $canonical['host'] ?? null;

        if ($canonicalHost === null) {
            return $next($request);
        }

        $requestHost = $request->getHost();

        if ($requestHost === 'www.'.$canonicalHost) {
            $scheme = $canonical['scheme'] ?? $request->getScheme();
            $target = $scheme.'://'.$canonicalHost.$request->getRequestUri();
            $status = in_array($request->getMethod(), ['GET', 'HEAD'], true) ? 301 : 308;

            return redirect()->to($target, $status);
        }

        return $next($request);
    }
}
