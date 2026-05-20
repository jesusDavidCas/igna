<?php

namespace App\Support\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BrandSettings
{
    public function publicPayload(): array
    {
        $defaults = [
            'company_name' => 'IGNA Studio',
            'logo_text' => 'IG',
            'logo_url' => null,
            'favicon_url' => null,
        ];

        if (! Schema::hasTable('settings')) {
            return $defaults;
        }

        $settings = Setting::query()
            ->whereIn('key', ['company_name', 'brand_logo_text', 'brand_logo_path', 'brand_favicon_path'])
            ->pluck('value', 'key');

        return [
            'company_name' => $settings->get('company_name') ?: $defaults['company_name'],
            'logo_text' => $settings->get('brand_logo_text') ?: $defaults['logo_text'],
            'logo_url' => $this->publicUrl($settings->get('brand_logo_path')),
            'favicon_url' => $this->publicUrl($settings->get('brand_favicon_path')),
        ];
    }

    public function pdfPayload(): array
    {
        $payload = $this->publicPayload();

        if (! Schema::hasTable('settings')) {
            return [...$payload, 'logo_data_uri' => null];
        }

        $logoPath = Setting::query()->where('key', 'brand_logo_path')->value('value');

        $payload['logo_data_uri'] = $this->dataUri($logoPath);

        return $payload;
    }

    private function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    private function dataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mimeType = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return 'data:'.$mimeType.';base64,'.base64_encode(Storage::disk('public')->get($path));
    }
}
