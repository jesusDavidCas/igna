<?php

namespace App\Services\Services;

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

        return $value;
    }
}
