<?php

namespace App\Enums;

enum StageEventStatus: string
{
    case PENDING = 'pending';
    case CURRENT = 'current';
    case COMPLETED = 'completed';
    case SKIPPED = 'skipped';

    public function label(): string
    {
        return __("site.stage_status_{$this->value}");
    }
}
