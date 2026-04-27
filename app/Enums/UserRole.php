<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case CLIENT = 'client';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => __('site.role_super_admin'),
            self::ADMIN => __('site.role_admin'),
            self::CLIENT => __('site.role_client'),
        };
    }

    public function canAccessAdmin(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::ADMIN], true);
    }
}
