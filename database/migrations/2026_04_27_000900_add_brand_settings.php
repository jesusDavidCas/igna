<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->settings() as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'group' => $setting['group'],
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'is_public' => $setting['is_public'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', array_column($this->settings(), 'key'))
            ->delete();
    }

    private function settings(): array
    {
        return [
            ['group' => 'branding', 'key' => 'brand_logo_text', 'value' => 'IG', 'type' => 'string', 'is_public' => true],
            ['group' => 'branding', 'key' => 'brand_logo_path', 'value' => null, 'type' => 'file', 'is_public' => true],
            ['group' => 'branding', 'key' => 'brand_favicon_path', 'value' => null, 'type' => 'file', 'is_public' => true],
        ];
    }
};
