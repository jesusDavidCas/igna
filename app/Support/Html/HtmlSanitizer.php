<?php

namespace App\Support\Html;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;

class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'a',
        'b',
        'blockquote',
        'br',
        'code',
        'em',
        'h2',
        'h3',
        'i',
        'li',
        'ol',
        'p',
        'pre',
        'strong',
        'ul',
    ];

    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math'];

    public function clean(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $wrapperId = 'igna-sanitizer-root';

        $loaded = $document->loadHTML(
            '<?xml encoding="utf-8" ?><div id="'.$wrapperId.'">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        if (! $loaded) {
            return '';
        }

        $root = $document->getElementById($wrapperId);

        if (! $root) {
            return '';
        }

        $this->sanitizeChildren($root);

        $clean = '';

        foreach (iterator_to_array($root->childNodes) as $child) {
            $clean .= $document->saveHTML($child);
        }

        return trim($clean);
    }

    private function sanitizeChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof DOMComment) {
                $parent->removeChild($node);

                continue;
            }

            if (! $node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $parent->removeChild($node);

                continue;
            }

            $this->sanitizeChildren($node);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }

                $parent->removeChild($node);

                continue;
            }

            $this->sanitizeAttributes($node);
        }
    }

    private function sanitizeAttributes(DOMElement $element): void
    {
        $tag = strtolower($element->tagName);
        $allowed = $tag === 'a' ? ['href', 'title', 'target'] : [];

        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! in_array(strtolower($attribute->name), $allowed, true)) {
                $element->removeAttributeNode($attribute);
            }
        }

        if ($tag !== 'a') {
            return;
        }

        $href = $this->safeHref($element->getAttribute('href'));

        if ($href === null) {
            $element->removeAttribute('href');
            $element->removeAttribute('target');
            $element->removeAttribute('rel');

            return;
        }

        $element->setAttribute('href', $href);

        if ($element->getAttribute('target') !== '_blank') {
            $element->removeAttribute('target');
        }

        if ($this->isExternalHttpsUrl($href)) {
            $element->setAttribute('rel', 'nofollow noopener noreferrer');
        } else {
            $element->removeAttribute('rel');
        }
    }

    private function safeHref(string $href): ?string
    {
        $decoded = trim($href);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $next = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if ($next === $decoded) {
                break;
            }

            $decoded = $next;
        }

        $decoded = trim($decoded);
        $normalized = preg_replace('/[\p{Z}\p{C}\s]+/u', '', $decoded) ?? '';

        if ($normalized === '' || str_starts_with($normalized, '//')) {
            return null;
        }

        if (str_starts_with($normalized, '#')) {
            return $normalized;
        }

        if (str_starts_with($normalized, '/')) {
            return $normalized;
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $normalized) === 1) {
            if (preg_match('/^https:/i', $normalized) !== 1) {
                return null;
            }

            $parts = parse_url($normalized);

            return is_array($parts) && isset($parts['host']) ? $normalized : null;
        }

        return $normalized;
    }

    private function isExternalHttpsUrl(string $href): bool
    {
        if (preg_match('/^https:/i', $href) !== 1) {
            return false;
        }

        $linkHost = parse_url($href, PHP_URL_HOST);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return is_string($linkHost)
            && ($appHost === null || strcasecmp($linkHost, (string) $appHost) !== 0);
    }
}
