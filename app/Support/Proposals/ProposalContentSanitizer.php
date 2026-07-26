<?php

namespace App\Support\Proposals;

class ProposalContentSanitizer
{
    private const ALLOWED_TAGS = '<p><br><strong><em><ul><ol><li>';

    public function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        if ($html === strip_tags($html)) {
            return $this->plainTextToHtml($html);
        }

        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? '';
        $html = preg_replace('/<(script|style|iframe|object|embed|form|input|img|video|audio|table|thead|tbody|tr|td|th|a|h[1-6])\b[^>]*>.*?<\/\1>/is', '', $html) ?? '';
        $html = preg_replace('/<(script|style|iframe|object|embed|form|input|img|video|audio|table|thead|tbody|tr|td|th|a|h[1-6])\b[^>]*\/?>/is', '', $html) ?? '';
        $html = preg_replace_callback('/<\/?(b)\b[^>]*>/i', fn (array $match): string => str_starts_with($match[0], '</') ? '</strong>' : '<strong>', $html) ?? '';
        $html = preg_replace_callback('/<\/?(i)\b[^>]*>/i', fn (array $match): string => str_starts_with($match[0], '</') ? '</em>' : '<em>', $html) ?? '';
        $html = strip_tags($html, self::ALLOWED_TAGS);
        $html = preg_replace_callback('/<([a-z0-9]+)\b[^>]*>/i', fn (array $match): string => in_array(strtolower($match[1]), ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li'], true) ? '<'.strtolower($match[1]).'>' : '', $html) ?? '';
        $html = preg_replace_callback('/<\/([a-z0-9]+)\b[^>]*>/i', fn (array $match): string => in_array(strtolower($match[1]), ['p', 'strong', 'em', 'ul', 'ol', 'li'], true) ? '</'.strtolower($match[1]).'>' : '', $html) ?? '';
        $html = preg_replace('/<(p|li)>\s*<\/\1>/i', '', $html) ?? '';

        return trim($html);
    }

    public function plainTextToHtml(?string $text): string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return '';
        }

        $paragraphs = preg_split('/\R{2,}/u', $text) ?: [];

        return collect($paragraphs)
            ->map(fn (string $paragraph): string => '<p>'.preg_replace('/\R/u', '<br>', e(trim($paragraph))).'</p>')
            ->implode('');
    }

    public function toPlainText(?string $html): string
    {
        $html = preg_replace('/<br\s*\/?>/i', "\n", $this->clean($html)) ?? '';
        $html = preg_replace('/<\/(p|li)>/i', "\n", $html) ?? '';

        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
