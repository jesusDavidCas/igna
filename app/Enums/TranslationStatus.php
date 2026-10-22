<?php

namespace App\Enums;

enum TranslationStatus: string
{
    case DRAFT = 'draft';
    case IN_REVIEW = 'in_review';
    case APPROVED = 'approved';
    case UNPUBLISHED = 'unpublished';
}
