<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['group' => 'company', 'key' => 'company_name', 'value' => 'IGNA Studio', 'type' => 'string', 'is_public' => true],
            ['group' => 'company', 'key' => 'support_email', 'value' => 'admin@ignastudio.com', 'type' => 'string', 'is_public' => true],
            ['group' => 'branding', 'key' => 'brand_logo_text', 'value' => 'IG', 'type' => 'string', 'is_public' => true],
            ['group' => 'branding', 'key' => 'brand_logo_path', 'value' => null, 'type' => 'file', 'is_public' => true],
            ['group' => 'branding', 'key' => 'brand_favicon_path', 'value' => null, 'type' => 'file', 'is_public' => true],
            ['group' => 'platform', 'key' => 'storage_backend', 'value' => 'google_drive_stub', 'type' => 'string', 'is_public' => false],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $setting,
            );
        }
    }
}
