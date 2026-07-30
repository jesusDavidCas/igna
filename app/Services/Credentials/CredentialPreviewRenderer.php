<?php

namespace App\Services\Credentials;

use App\Models\TeamCredential;
use FPDF;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

class CredentialPreviewRenderer
{
    public const PROTECTION_VERSION = 2;

    private const RASTER_DPI = 170;
    private const JPEG_QUALITY = 88;

    public function pageCount(string $absolutePath, ?string $mimeType): int
    {
        if (! $this->isPdf($absolutePath, $mimeType)) {
            return 1;
        }

        try {
            $pdf = new Fpdi;

            return max(1, $pdf->setSourceFile($absolutePath));
        } catch (Throwable) {
            return 0;
        }
    }

    public function generateProtectedDerivative(TeamCredential $credential): TeamCredential
    {
        $disk = Storage::disk('local');
        $hasPreviousDerivative = $credential->protected_document_path
            && $disk->exists($credential->protected_document_path);

        if (! $credential->document_path || ! $disk->exists($credential->document_path)) {
            return $this->markFailed(
                $credential,
                'Original credential file is unavailable.',
                preserveReadyDerivative: $hasPreviousDerivative,
            );
        }

        $credential->forceFill([
            'protection_status' => 'generating',
            'protection_error' => null,
            'protection_version' => self::PROTECTION_VERSION,
        ])->save();

        $oldProtectedPath = $credential->protected_document_path;
        $absolutePath = $disk->path($credential->document_path);

        try {
            $contents = $this->renderProtectedPdf($absolutePath, $credential->mime_type);
            $protectedPath = $this->protectedPath($credential);

            $disk->put($protectedPath, $contents);

            if (! $disk->exists($protectedPath) || $disk->size($protectedPath) === 0) {
                $disk->delete($protectedPath);

                throw new RuntimeException('Generated protected credential copy was empty.');
            }

            $credential->forceFill([
                'protected_document_path' => $protectedPath,
                'original_checksum' => hash_file('sha256', $absolutePath),
                'protected_checksum' => hash('sha256', $contents),
                'protected_generated_at' => now(),
                'protection_version' => self::PROTECTION_VERSION,
                'protection_status' => 'ready',
                'protection_error' => null,
                'preview_page_count' => $this->pageCount($absolutePath, $credential->mime_type),
            ])->save();

            if ($oldProtectedPath && $oldProtectedPath !== $protectedPath) {
                $disk->delete($oldProtectedPath);
            }

            return $credential->refresh();
        } catch (Throwable $exception) {
            Log::warning('Team credential protected derivative generation failed.', [
                'credential_id' => $credential->id,
                'mime_type' => $credential->mime_type,
                'has_previous_derivative' => (bool) $oldProtectedPath,
                'error' => $this->safeError($exception),
            ]);

            return $this->markFailed(
                $credential,
                $this->safeError($exception),
                preserveReadyDerivative: $oldProtectedPath && $disk->exists($oldProtectedPath),
            );
        }
    }

    public function renderJpeg(string $absolutePath, ?string $mimeType, int $page = 1): string
    {
        $pages = $this->rasterizeToWatermarkedJpegs($absolutePath, $mimeType, max(1, $page), max(1, $page));

        try {
            return $pages[0] ? file_get_contents($pages[0]) : throw new RuntimeException('Credential preview could not be rendered.');
        } finally {
            $this->removeTemporaryDirectory(dirname($pages[0] ?? ''));
        }
    }

    public function renderProtectedFile(string $absolutePath, ?string $mimeType): array
    {
        return [
            'contents' => $this->renderProtectedPdf($absolutePath, $mimeType),
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
        ];
    }

    private function renderProtectedPdf(string $absolutePath, ?string $mimeType): string
    {
        $pages = $this->rasterizeToWatermarkedJpegs($absolutePath, $mimeType);

        try {
            $pdf = new FPDF('P', 'mm');
            $pdf->SetCompression(true);
            $pdf->SetAutoPageBreak(false);
            $pdf->SetMargins(0, 0, 0);

            foreach ($pages as $pagePath) {
                $size = getimagesize($pagePath);

                if ($size === false) {
                    throw new RuntimeException('Rasterized credential page could not be measured.');
                }

                [$width, $height] = $size;
                $pageWidth = max(1, $width * 25.4 / self::RASTER_DPI);
                $pageHeight = max(1, $height * 25.4 / self::RASTER_DPI);
                $orientation = $pageWidth > $pageHeight ? 'L' : 'P';

                $pdf->AddPage($orientation, [$pageWidth, $pageHeight]);
                $pdf->Image($pagePath, 0, 0, $pageWidth, $pageHeight, 'JPEG');
            }

            return $pdf->Output('S');
        } finally {
            $this->removeTemporaryDirectory(dirname($pages[0] ?? ''));
        }
    }

    /**
     * @return list<string>
     */
    private function rasterizeToWatermarkedJpegs(string $absolutePath, ?string $mimeType, ?int $firstPage = null, ?int $lastPage = null): array
    {
        $temporaryDirectory = $this->temporaryDirectory();

        try {
            $pagePaths = $this->isPdf($absolutePath, $mimeType)
                ? $this->rasterizePdf($absolutePath, $temporaryDirectory, $firstPage, $lastPage)
                : [$this->normalizeImage($absolutePath, $temporaryDirectory)];

            if ($pagePaths === []) {
                throw new RuntimeException('No credential pages were rasterized.');
            }

            $watermarked = [];

            foreach ($pagePaths as $index => $pagePath) {
                $watermarked[] = $this->watermarkImageFile($pagePath, $temporaryDirectory.'/page-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT).'-protected.jpg');
            }

            return $watermarked;
        } catch (Throwable $exception) {
            $this->removeTemporaryDirectory($temporaryDirectory);

            throw $exception;
        }
    }

    /**
     * @return list<string>
     */
    private function rasterizePdf(string $absolutePath, string $temporaryDirectory, ?int $firstPage, ?int $lastPage): array
    {
        $pdftoppm = $this->pdftoppmPath();

        if (! $pdftoppm) {
            throw new RuntimeException('PDF rasterization requires the pdftoppm executable from Poppler.');
        }

        $prefix = $temporaryDirectory.'/source-page';
        $arguments = [$pdftoppm, '-jpeg', '-r', (string) self::RASTER_DPI];

        if ($firstPage !== null) {
            $arguments[] = '-f';
            $arguments[] = (string) $firstPage;
        }

        if ($lastPage !== null) {
            $arguments[] = '-l';
            $arguments[] = (string) $lastPage;
        }

        $arguments[] = $absolutePath;
        $arguments[] = $prefix;

        $process = new Process($arguments);
        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('PDF rasterization failed with code '.$process->getExitCode().': '.$this->sanitizeProcessOutput($process->getErrorOutput() ?: $process->getOutput()));
        }

        $pages = [
            ...(glob($temporaryDirectory.'/source-page-*.jpg') ?: []),
            ...(glob($temporaryDirectory.'/source-page-*.jpeg') ?: []),
        ];
        natsort($pages);

        $pages = array_values(array_filter($pages, fn (string $path): bool => is_file($path) && filesize($path) > 0));

        if ($pages === []) {
            throw new RuntimeException('PDF rasterization produced no readable page images.');
        }

        return $pages;
    }

    private function normalizeImage(string $absolutePath, string $temporaryDirectory): string
    {
        $source = $this->loadImage($absolutePath);
        $source = $this->applyExifOrientation($source, $absolutePath);

        $width = imagesx($source);
        $height = imagesy($source);
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);

        $path = $temporaryDirectory.'/source-page-001.jpg';
        imagejpeg($canvas, $path, self::JPEG_QUALITY);

        return $path;
    }

    private function watermarkImageFile(string $sourcePath, string $targetPath): string
    {
        $image = $this->loadImage($sourcePath);
        $width = imagesx($image);
        $height = imagesy($image);
        $maxWidth = 2200;

        if ($width > $maxWidth) {
            $ratio = $maxWidth / $width;
            $resized = imagescale($image, $maxWidth, (int) round($height * $ratio));
            $image = $resized ?: throw new RuntimeException('Credential raster resize failed.');
            $width = imagesx($image);
            $height = imagesy($image);
        }

        $this->applyWatermarks($image, $width, $height);
        imagejpeg($image, $targetPath, self::JPEG_QUALITY);

        if (! is_file($targetPath) || filesize($targetPath) === 0) {
            throw new RuntimeException('Credential protected raster page could not be written.');
        }

        return $targetPath;
    }

    private function loadImage(string $path): \GdImage
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('Credential raster source could not be read.');
        }

        $image = imagecreatefromstring($contents);

        if (! $image) {
            throw new RuntimeException('Credential raster source could not be decoded.');
        }

        return $image;
    }

    private function applyExifOrientation(\GdImage $source, string $absolutePath): \GdImage
    {
        if (! extension_loaded('exif') || ! function_exists('exif_read_data')) {
            return $source;
        }

        $type = @exif_imagetype($absolutePath);

        if (! in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM], true)) {
            return $source;
        }

        $exif = @exif_read_data($absolutePath);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        return match ($orientation) {
            3 => imagerotate($source, 180, 0) ?: $source,
            6 => imagerotate($source, -90, 0) ?: $source,
            8 => imagerotate($source, 90, 0) ?: $source,
            default => $source,
        };
    }

    private function applyWatermarks(\GdImage $image, int $width, int $height): void
    {
        $font = $this->fontPath();
        $primary = imagecolorallocatealpha($image, 42, 42, 36, 70);
        $warning = imagecolorallocatealpha($image, 120, 48, 48, 58);
        $small = imagecolorallocatealpha($image, 80, 72, 64, 42);

        if ($font) {
            for ($y = -120; $y < $height + 220; $y += max(190, (int) ($height / 4.5))) {
                for ($x = -220; $x < $width + 260; $x += max(420, (int) ($width / 2.8))) {
                    imagettftext($image, max(28, (int) ($width / 34)), -25, $x, $y, $primary, $font, 'IGNA Studio');
                    imagettftext($image, max(13, (int) ($width / 92)), -25, $x + 28, $y + 48, $warning, $font, 'Copia protegida de credencial');
                    imagettftext($image, max(13, (int) ($width / 92)), -25, $x + 28, $y + 76, $warning, $font, 'Protected credential copy');
                }
            }

            imagettftext($image, max(12, (int) ($width / 115)), 0, 28, $height - 30, $small, $font, 'IGNA Studio - Copia protegida de credencial / Protected credential copy');

            return;
        }

        for ($y = 30; $y < $height; $y += 120) {
            for ($x = 20; $x < $width; $x += 260) {
                imagestring($image, 5, $x, $y, 'IGNA Studio', $primary);
                imagestring($image, 3, $x, $y + 24, 'Copia protegida', $warning);
                imagestring($image, 3, $x, $y + 44, 'Protected copy', $warning);
            }
        }
    }

    private function protectedPath(TeamCredential $credential): string
    {
        $teamMember = $credential->teamMember;
        $slug = $teamMember?->slug ?: 'team-member';

        return 'team/credentials/'.$slug.'/protected/'.$credential->id.'-v'.self::PROTECTION_VERSION.'-'.Str::uuid().'.pdf';
    }

    private function markFailed(TeamCredential $credential, string $error, bool $preserveReadyDerivative = false): TeamCredential
    {
        $credential->forceFill([
            'protection_status' => $preserveReadyDerivative && $credential->protected_document_path ? 'ready' : 'failed',
            'protection_error' => $error,
            'protection_version' => self::PROTECTION_VERSION,
        ])->save();

        return $credential->refresh();
    }

    private function safeError(Throwable|string $error): string
    {
        $message = $error instanceof Throwable ? $error->getMessage() : $error;

        if (str_contains($message, 'pdftoppm')) {
            return 'PDF rasterization requires Poppler pdftoppm on the server.';
        }

        return Str::limit(preg_replace('/\s+/', ' ', $message) ?: 'Protected credential generation failed.', 180, '');
    }

    private function sanitizeProcessOutput(string $output): string
    {
        $output = preg_replace('/[A-Za-z]:?[^\\s]+(?:\\/[^\\s]+)+/', '[path]', $output) ?? $output;
        $output = preg_replace('/\\/[^\\s]+(?:\\/[^\\s]+)+/', '[path]', $output) ?? $output;

        return Str::limit(preg_replace('/\s+/', ' ', trim($output)) ?: 'process failed', 120, '');
    }

    private function pdftoppmPath(): ?string
    {
        $configured = config('services.poppler.pdftoppm');

        if (is_string($configured) && $configured !== '' && is_executable($configured)) {
            return $configured;
        }

        $found = (new ExecutableFinder)->find('pdftoppm');

        if ($found && is_executable($found)) {
            return $found;
        }

        $home = rtrim((string) (getenv('HOME') ?: ''), '/');
        $candidates = array_filter([
            '/usr/bin/pdftoppm',
            '/usr/local/bin/pdftoppm',
            '/opt/homebrew/bin/pdftoppm',
            $home !== '' ? $home.'/.local/bin/pdftoppm' : null,
            $home !== '' ? $home.'/.cache/codex-runtimes/codex-primary-runtime/dependencies/bin/override/pdftoppm' : null,
        ]);

        foreach ($candidates as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function temporaryDirectory(): string
    {
        $path = storage_path('app/tmp/credential-protection/'.Str::uuid());

        if (! is_dir($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
            throw new RuntimeException('Credential raster temporary directory could not be created.');
        }

        return $path;
    }

    private function removeTemporaryDirectory(string $directory): void
    {
        if ($directory === '' || ! is_dir($directory) || ! str_contains($directory, 'credential-protection')) {
            return;
        }

        foreach (glob($directory.'/*') ?: [] as $path) {
            @unlink($path);
        }

        @rmdir($directory);
    }

    private function isPdf(string $absolutePath, ?string $mimeType): bool
    {
        return $mimeType === 'application/pdf' || str_ends_with(strtolower($absolutePath), '.pdf');
    }

    private function fontPath(): ?string
    {
        foreach ([
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
            '/Library/Fonts/Arial Bold.ttf',
        ] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
