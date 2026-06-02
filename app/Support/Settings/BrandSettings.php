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
            return [...$payload, 'logo_data_uri' => $this->defaultPdfLogoDataUri()];
        }

        $logoPath = Setting::query()->where('key', 'brand_logo_path')->value('value');

        $payload['logo_data_uri'] = $this->dataUri($logoPath, trimWhitespace: true) ?? $this->defaultPdfLogoDataUri();

        return $payload;
    }

    private function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return url(Storage::disk('public')->url($path));
    }

    private function dataUri(?string $path, bool $trimWhitespace = false): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $contents = Storage::disk('public')->get($path);

        if ($trimWhitespace && $trimmedLogo = $this->trimLogoWhitespace($contents)) {
            return 'data:image/png;base64,'.base64_encode($trimmedLogo);
        }

        $mimeType = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    private function trimLogoWhitespace(string $contents): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $source = @imagecreatefromstring($contents);

        if (! $source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $hasTransparency = false;

        for ($y = 0; $y < $height && ! $hasTransparency; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $alpha = (imagecolorat($source, $x, $y) & 0x7F000000) >> 24;

                if ($alpha > 110) {
                    $hasTransparency = true;
                    break;
                }
            }
        }

        $minX = $width;
        $minY = $height;
        $maxX = -1;
        $maxY = -1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($source, $x, $y);
                $alpha = ($color & 0x7F000000) >> 24;
                $red = ($color >> 16) & 0xFF;
                $green = ($color >> 8) & 0xFF;
                $blue = $color & 0xFF;
                $isContent = $hasTransparency
                    ? $alpha <= 110
                    : ! ($red > 248 && $green > 248 && $blue > 248);

                if (! $isContent) {
                    continue;
                }

                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }

        if ($maxX < $minX || $maxY < $minY) {
            imagedestroy($source);

            return null;
        }

        $padding = 12;
        $cropX = max(0, $minX - $padding);
        $cropY = max(0, $minY - $padding);
        $cropWidth = min($width - $cropX, ($maxX - $minX + 1) + ($padding * 2));
        $cropHeight = min($height - $cropY, ($maxY - $minY + 1) + ($padding * 2));

        if ($cropWidth >= $width && $cropHeight >= $height) {
            imagedestroy($source);

            return null;
        }

        $cropped = imagecreatetruecolor($cropWidth, $cropHeight);
        $white = imagecolorallocate($cropped, 255, 255, 255);
        imagefill($cropped, 0, 0, $white);
        imagecopy($cropped, $source, 0, 0, $cropX, $cropY, $cropWidth, $cropHeight);

        ob_start();
        imagepng($cropped);
        $trimmed = ob_get_clean();

        imagedestroy($source);
        imagedestroy($cropped);

        return $trimmed ?: null;
    }

    private function defaultPdfLogoDataUri(): string
    {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="380" height="116" viewBox="0 0 380 116" role="img" aria-label="IGNA Studio">
  <rect width="380" height="116" fill="#ffffff"/>
  <circle cx="58" cy="58" r="37" fill="none" stroke="#0f0f0f" stroke-width="5"/>
  <rect x="44" y="44" width="28" height="28" rx="4" transform="rotate(45 58 58)" fill="none" stroke="#0f0f0f" stroke-width="5"/>
  <text x="118" y="68" fill="#0f0f0f" font-family="DejaVu Sans, Arial, sans-serif" font-size="31" font-weight="700" letter-spacing="6">IGNA STUDIO</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
