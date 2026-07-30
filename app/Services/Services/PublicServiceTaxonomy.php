<?php

namespace App\Services\Services;

use App\Models\Service;
use Illuminate\Support\Collection;

class PublicServiceTaxonomy
{
    public const TECHNOLOGY = 'technology';
    public const INFRASTRUCTURE_ENGINEERING = 'infrastructure_engineering';
    public const OTHER = 'other';

    /**
     * @return array<string, int>
     */
    public function order(): array
    {
        return [
            self::TECHNOLOGY => 1,
            self::INFRASTRUCTURE_ENGINEERING => 2,
            self::OTHER => 3,
        ];
    }

    /**
     * @return array<string>
     */
    public function codes(): array
    {
        return array_keys($this->order());
    }

    public function fromBusinessLine(?string $businessLine): string
    {
        return match ($businessLine) {
            'digital' => self::TECHNOLOGY,
            'engineering' => self::INFRASTRUCTURE_ENGINEERING,
            default => self::OTHER,
        };
    }

    public function label(string $code): string
    {
        return __("site.service_public_category_{$code}");
    }

    public function otherLabel(): string
    {
        return __('site.service_public_category_other');
    }

    /**
     * @param  Collection<int, Service>  $services
     * @return Collection<string, Collection<int, Service>>
     */
    public function groupServices(Collection $services): Collection
    {
        $order = $this->order();

        return $services
            ->sortBy([
                fn (Service $service): int => $order[$service->publicCategoryCode()] ?? $order[self::OTHER],
                fn (Service $service): int => $service->sort_order,
                fn (Service $service): string => $service->localizedName(),
                fn (Service $service): int => $service->id,
            ])
            ->groupBy(fn (Service $service): string => $service->publicCategoryCode())
            ->sortBy(fn (Collection $group, string $code): int => $order[$code] ?? $order[self::OTHER]);
    }
}
