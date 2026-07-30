<?php

namespace App\Services\Services;

class ServiceDeliverableNormalizer
{
    /**
     * @param  mixed  $value
     * @return array<int, array{en: string, es: string}>
     */
    public function rows(mixed $value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->map(function (mixed $row): array {
                    if (is_array($row)) {
                        return [
                            'en' => trim((string) ($row['en'] ?? $row['name_en'] ?? $row['name'] ?? '')),
                            'es' => trim((string) ($row['es'] ?? $row['name_es'] ?? '')),
                        ];
                    }

                    return ['en' => trim((string) $row), 'es' => ''];
                })
                ->filter(fn (array $row): bool => $row['en'] !== '' || $row['es'] !== '')
                ->values()
                ->all();
        }

        return collect(preg_split('/\r\n|\r|\n/', (string) $value) ?: [])
            ->flatMap(fn (string $line): array => str_contains($line, '|') ? explode('|', $line) : [$line])
            ->map(fn (string $line): array => ['en' => trim($line), 'es' => ''])
            ->filter(fn (array $row): bool => $row['en'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $value
     * @return array<int, string>
     */
    public function legacyList(mixed $value): array
    {
        return collect($this->rows($value))
            ->map(fn (array $row): string => $row['en'] ?: $row['es'])
            ->filter()
            ->values()
            ->all();
    }
}
