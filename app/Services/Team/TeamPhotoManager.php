<?php

namespace App\Services\Team;

use App\Models\TeamMember;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TeamPhotoManager
{
    private const DIRECTORY = 'team/photos';
    private const MAX_RENDER_DIMENSION = 1600;
    private const JPEG_QUALITY = 86;

    /**
     * @return array{contents: string, mime_type: string, etag: string}|null
     */
    public function publicFileFor(TeamMember $teamMember): ?array
    {
        $path = $teamMember->photo_path;

        if (! $this->isApprovedPath($path)) {
            return null;
        }

        $disk = Storage::disk('public');

        try {
            if (! $disk->exists($path)) {
                return null;
            }

            $contents = $disk->get($path);
            $image = @getimagesizefromstring($contents);

            if (! is_array($image)) {
                return null;
            }

            $mimeType = $image['mime'] ?? null;

            if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                return null;
            }

            $lastModified = $disk->lastModified($path);
            $size = $disk->size($path);
        } catch (Throwable) {
            return null;
        }

        return [
            'contents' => $contents,
            'mime_type' => $mimeType,
            'etag' => '"'.sha1($teamMember->getKey().'|'.$teamMember->updated_at?->timestamp.'|'.$lastModified.'|'.$size).'"',
        ];
    }

    /**
     * @return array{contents: string, mime_type: string, etag: string}
     */
    public function fallbackFileFor(TeamMember $teamMember): array
    {
        $contents = $this->initialsPng($teamMember->initials());

        return [
            'contents' => $contents,
            'mime_type' => 'image/png',
            'etag' => '"'.sha1('team-photo-fallback|'.$teamMember->getKey().'|'.$teamMember->name.'|'.$teamMember->updated_at?->timestamp).'"',
        ];
    }

    public function store(UploadedFile $file): string
    {
        $path = self::DIRECTORY.'/'.Str::uuid().'.jpg';

        Storage::disk('public')->put($path, $this->normalizedJpeg($file));

        return $path;
    }

    public function deleteIfUnreferenced(?string $path): void
    {
        if (! $this->isApprovedPath($path)) {
            return;
        }

        if (TeamMember::query()->where('photo_path', $path)->exists()) {
            return;
        }

        try {
            Storage::disk('public')->delete($path);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function isApprovedPath(?string $path): bool
    {
        if (! is_string($path) || $path === '') {
            return false;
        }

        if ($path !== trim($path, " \t\n\r\0\x0B/")) {
            return false;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $path)) {
            return false;
        }

        return str_starts_with($path, self::DIRECTORY.'/')
            && ! str_contains($path, '..')
            && ! str_contains($path, '\\')
            && basename($path) !== '';
    }

    private function normalizedJpeg(UploadedFile $file): string
    {
        $sourcePath = $file->getRealPath();
        $contents = $sourcePath ? @file_get_contents($sourcePath) : false;

        if (! is_string($contents)) {
            throw new RuntimeException('Team photo upload could not be read.');
        }

        $source = @imagecreatefromstring($contents);

        if (! $source) {
            throw new RuntimeException('Team photo upload could not be decoded.');
        }

        if ($sourcePath && $this->isJpeg($sourcePath)) {
            $source = $this->orientJpeg($source, $sourcePath);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $ratio = min(1, self::MAX_RENDER_DIMENSION / max($width, $height));

        if ($ratio < 1) {
            $targetWidth = max(1, (int) round($width * $ratio));
            $targetHeight = max(1, (int) round($height * $ratio));
            $resized = imagecreatetruecolor($targetWidth, $targetHeight);
            $white = imagecolorallocate($resized, 255, 255, 255);
            imagefill($resized, 0, 0, $white);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
            $source = $resized;
        }

        ob_start();
        $encoded = imagejpeg($source, null, self::JPEG_QUALITY);
        $jpeg = ob_get_clean();

        if (! $encoded || ! is_string($jpeg)) {
            throw new RuntimeException('Team photo upload could not be normalized.');
        }

        return $jpeg;
    }

    private function isJpeg(string $path): bool
    {
        return @exif_imagetype($path) === IMAGETYPE_JPEG;
    }

    private function orientJpeg(\GdImage $source, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $source;
        }

        $exif = @exif_read_data($path);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        return match ($orientation) {
            3 => imagerotate($source, 180, 0) ?: $source,
            6 => imagerotate($source, -90, 0) ?: $source,
            8 => imagerotate($source, 90, 0) ?: $source,
            default => $source,
        };
    }

    private function initialsPng(string $initials): string
    {
        $image = imagecreatetruecolor(640, 800);
        $background = imagecolorallocate($image, 86, 102, 62);
        $foreground = imagecolorallocate($image, 255, 255, 255);

        imagefill($image, 0, 0, $background);

        $text = $initials !== '' ? $initials : 'IS';
        $font = 5;
        $x = (int) ((640 - imagefontwidth($font) * strlen($text)) / 2);
        $y = (int) ((800 - imagefontheight($font)) / 2);

        imagestring($image, $font, $x, $y, $text, $foreground);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();

        return is_string($png) ? $png : '';
    }
}
