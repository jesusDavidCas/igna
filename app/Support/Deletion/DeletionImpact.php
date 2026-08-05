<?php

namespace App\Support\Deletion;

use Illuminate\Contracts\Support\Arrayable;

class DeletionImpact implements Arrayable
{
    /**
     * @param  array<string, int>  $counts
     * @param  array<int, string>  $deleteItems
     * @param  array<int, string>  $preserveItems
     * @param  array<int, string>  $blockingKeys
     */
    public function __construct(
        private readonly array $counts = [],
        private readonly array $deleteItems = [],
        private readonly array $preserveItems = [],
        private readonly array $blockingKeys = [],
    ) {}

    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        return $this->counts;
    }

    /**
     * @return array<int, string>
     */
    public function deleteItems(): array
    {
        return $this->deleteItems;
    }

    /**
     * @return array<int, string>
     */
    public function preserveItems(): array
    {
        return $this->preserveItems;
    }

    /**
     * @return array<string, int>
     */
    public function blockingCounts(): array
    {
        return collect($this->blockingKeys)
            ->mapWithKeys(fn (string $key): array => [$key => (int) ($this->counts[$key] ?? 0)])
            ->filter(fn (int $count): bool => $count > 0)
            ->all();
    }

    public function canDelete(): bool
    {
        return $this->blockingCounts() === [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'counts' => $this->counts,
            'delete_items' => $this->deleteItems,
            'preserve_items' => $this->preserveItems,
            'blocking_counts' => $this->blockingCounts(),
        ];
    }
}
