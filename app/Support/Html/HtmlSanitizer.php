<?php

namespace App\Support\Html;

class HtmlSanitizer
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><ul><ol><li><a><h2><h3><blockquote><code><pre>';

    public function clean(string $html): string
    {
        $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $html) ?? '';
        $html = strip_tags($html, self::ALLOWED_TAGS);

        // Blog content is admin-managed but still rendered as HTML, so remove executable attributes defensively.
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/\s+style\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/href\s*=\s*([\'"])\s*javascript:[^\'"]*\1/i', 'href="#"', $html) ?? '';

        return trim($html);
    }
}
