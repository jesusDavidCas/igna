<?php

namespace App\Services\Services;

use RuntimeException;

class ServiceContentTranslator
{
    public function translate(?string $value, string $sourceLocale, string $targetLocale): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if ($sourceLocale === $targetLocale) {
            return $value;
        }

        throw new RuntimeException('No functional dynamic-content translation provider is configured.');
    }

    public function isUsableTranslation(?string $source, ?string $target): bool
    {
        $source = $this->normalizeForComparison($source);
        $target = $this->normalizeForComparison($target);

        return $target !== '' && $target !== $source;
    }

    private function normalizeForComparison(?string $value): string
    {
        $value = html_entity_decode(trim((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_strtolower(trim($value));
    }
}
