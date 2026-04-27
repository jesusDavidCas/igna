<?php

namespace App\Enums;

enum BlogPostStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';

    public function label(): string
    {
        return __("site.{$this->value}");
    }
}
