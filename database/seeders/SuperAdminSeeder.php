<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'admin@ignastudio.com');
        $password = env('SUPER_ADMIN_PASSWORD');

        if (! $password && app()->environment('production')) {
            throw new RuntimeException('SUPER_ADMIN_PASSWORD must be configured before seeding production.');
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'first_name' => env('SUPER_ADMIN_FIRST_NAME', 'IGNA'),
                'last_name' => env('SUPER_ADMIN_LAST_NAME', 'Administrator'),
                'phone' => env('SUPER_ADMIN_PHONE', '+57 300 000 0000'),
                'preferred_language' => env('SUPER_ADMIN_LANGUAGE', 'es'),
                'role' => UserRole::SUPER_ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => $password ?: 'Igna12345!',
            ],
        );
    }
}
