<?php

namespace App\Services\Blog;

use App\Models\BlogPost;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class BlogHeaderImageManager
{
    private const DIRECTORY = 'blog/headers';

    /**
     * @return array{contents: string, mime_type: string, etag: string}|null
     */
    public function publicFileFor(BlogPost $post): ?array
    {
        $path = $post->header_image_path;

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
            'etag' => '"'.sha1($post->getKey().'|'.$post->updated_at?->timestamp.'|'.$path.'|'.$lastModified.'|'.$size).'"',
        ];
    }

    public function hasPublicFile(BlogPost $post): bool
    {
        return $this->publicFileFor($post) !== null;
    }

    public function store(UploadedFile $file): string
    {
        $contents = $this->validatedImageContents($file);
        $path = self::DIRECTORY.'/'.Str::uuid().'.'.$this->extensionFor($contents);

        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    public function delete(?string $path): void
    {
        if (! $this->isApprovedPath($path)) {
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

    private function validatedImageContents(UploadedFile $file): string
    {
        $sourcePath = $file->getRealPath();
        $contents = $sourcePath ? @file_get_contents($sourcePath) : false;

        if (! is_string($contents)) {
            throw new RuntimeException('Blog header image upload could not be read.');
        }

        $image = @getimagesizefromstring($contents);
        $mimeType = is_array($image) ? ($image['mime'] ?? null) : null;

        if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('Blog header image upload is not a supported image.');
        }

        return $contents;
    }

    private function extensionFor(string $contents): string
    {
        $image = @getimagesizefromstring($contents);
        $mimeType = is_array($image) ? ($image['mime'] ?? null) : null;

        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'bin',
        };
    }
}
