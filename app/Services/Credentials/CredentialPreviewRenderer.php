<?php

namespace App\Services\Credentials;

use RuntimeException;
use setasign\Fpdi\Fpdi;

class CredentialPreviewRenderer
{
    public function pageCount(string $absolutePath, ?string $mimeType): int
    {
        if (! $this->isPdf($absolutePath, $mimeType)) {
            return 1;
        }

        try {
            $pdf = new Fpdi;

            return max(1, $pdf->setSourceFile($absolutePath));
        } catch (\Throwable) {
            return 0;
        }
    }

    public function renderJpeg(string $absolutePath, ?string $mimeType, int $page = 1): string
    {
        $page = max(1, $page);

        if ($this->isPdf($absolutePath, $mimeType)) {
            return $this->renderPdfPage($absolutePath, $page);
        }

        return $this->renderImage($absolutePath);
    }

    public function renderProtectedFile(string $absolutePath, ?string $mimeType): array
    {
        if ($this->isPdf($absolutePath, $mimeType)) {
            return [
                'contents' => $this->renderWatermarkedPdf($absolutePath),
                'mime_type' => 'application/pdf',
                'extension' => 'pdf',
            ];
        }

        return [
            'contents' => $this->renderImage($absolutePath),
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
        ];
    }

    private function renderWatermarkedPdf(string $absolutePath): string
    {
        try {
            $pdf = new class extends Fpdi
            {
                private float $angle = 0.0;

                public function rotate(float $angle, float $x = -1, float $y = -1): void
                {
                    if ($x === -1.0) {
                        $x = $this->x;
                    }

                    if ($y === -1.0) {
                        $y = $this->y;
                    }

                    if ($this->angle !== 0.0) {
                        $this->_out('Q');
                    }

                    $this->angle = $angle;

                    if ($angle === 0.0) {
                        return;
                    }

                    $angle *= M_PI / 180;
                    $cos = cos($angle);
                    $sin = sin($angle);
                    $cx = $x * $this->k;
                    $cy = ($this->h - $y) * $this->k;

                    $this->_out(sprintf(
                        'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
                        $cos,
                        $sin,
                        -$sin,
                        $cos,
                        $cx,
                        $cy,
                        -$cx,
                        -$cy
                    ));
                }

                protected function _endpage(): void
                {
                    if ($this->angle !== 0.0) {
                        $this->angle = 0.0;
                        $this->_out('Q');
                    }

                    parent::_endpage();
                }
            };

            $pageCount = $pdf->setSourceFile($absolutePath);
            $pdf->SetCompression(false);
            $pdf->SetAutoPageBreak(false);
            $pdf->SetMargins(0, 0, 0);

            for ($page = 1; $page <= $pageCount; $page++) {
                $template = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($template);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template, 0, 0, $size['width'], $size['height']);
                $this->applyPdfWatermarks($pdf, (float) $size['width'], (float) $size['height']);
            }

            return $pdf->Output('S');
        } catch (\Throwable $exception) {
            throw new RuntimeException('Credential PDF could not be watermarked for protected viewing.', previous: $exception);
        }
    }

    private function applyPdfWatermarks(Fpdi $pdf, float $width, float $height): void
    {
        $pdf->SetFont('Helvetica', 'B', max(24, min(44, (int) ($width / 6))));
        $pdf->SetTextColor(124, 118, 103);

        for ($y = -20; $y < $height + 80; $y += 58) {
            for ($x = -35; $x < $width + 80; $x += 118) {
                $pdf->rotate(35, $x, $y);
                $pdf->Text($x, $y, 'IGNA STUDIO');
                $pdf->rotate(0);
            }
        }

        $pdf->SetFont('Helvetica', 'B', max(8, min(13, (int) ($width / 28))));
        $pdf->SetTextColor(150, 54, 54);

        for ($y = 16; $y < $height + 30; $y += 46) {
            for ($x = 8; $x < $width + 40; $x += 94) {
                $pdf->rotate(35, $x, $y);
                $pdf->Text($x, $y, 'DOCUMENTO NO CONTROLADO');
                $pdf->Text($x, $y + 6, 'SOLO PARA CONSULTA');
                $pdf->Text($x, $y + 12, 'UNCONTROLLED DOCUMENT');
                $pdf->Text($x, $y + 18, 'FOR REFERENCE ONLY');
                $pdf->rotate(0);
            }
        }

        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetTextColor(80, 74, 66);
        $pdf->SetXY(8, $height - 10);
        $pdf->Cell(0, 5, 'IGNA Studio - Documento no controlado / For reference only', 0, 0, 'L');
    }

    private function renderPdfPage(string $absolutePath, int $page): string
    {
        if (! extension_loaded('imagick')) {
            throw new RuntimeException('PDF preview rendering requires the Imagick PHP extension.');
        }

        $document = new \Imagick;
        $document->setResolution(140, 140);
        $document->readImage($absolutePath.'['.($page - 1).']');
        $document->setImageBackgroundColor('white');
        $document = $document->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
        $document->setImageFormat('jpeg');
        $document->setImageCompressionQuality(88);

        return $this->watermarkJpegBlob($document->getImageBlob());
    }

    private function renderImage(string $absolutePath): string
    {
        $contents = file_get_contents($absolutePath);

        if ($contents === false) {
            throw new RuntimeException('Credential preview file could not be read.');
        }

        return $this->watermarkJpegBlob($contents);
    }

    private function watermarkJpegBlob(string $blob): string
    {
        $source = imagecreatefromstring($blob);

        if (! $source) {
            throw new RuntimeException('Credential preview could not be rendered.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $maxWidth = 1500;

        if ($width > $maxWidth) {
            $ratio = $maxWidth / $width;
            $resized = imagescale($source, $maxWidth, (int) round($height * $ratio));
            imagedestroy($source);
            $source = $resized ?: throw new RuntimeException('Credential preview resize failed.');
            $width = imagesx($source);
            $height = imagesy($source);
        }

        $this->applyWatermarks($source, $width, $height);

        ob_start();
        imagejpeg($source, null, 86);
        $jpeg = (string) ob_get_clean();
        imagedestroy($source);

        return $jpeg;
    }

    private function applyWatermarks(\GdImage $image, int $width, int $height): void
    {
        $font = $this->fontPath();
        $primary = imagecolorallocatealpha($image, 42, 42, 36, 72);
        $warning = imagecolorallocatealpha($image, 120, 48, 48, 58);
        $small = imagecolorallocatealpha($image, 80, 72, 64, 42);

        if ($font) {
            for ($y = -120; $y < $height + 220; $y += 260) {
                for ($x = -220; $x < $width + 260; $x += 520) {
                    imagettftext($image, max(34, (int) ($width / 30)), -25, $x, $y, $primary, $font, 'IGNA STUDIO');
                    imagettftext($image, max(16, (int) ($width / 78)), -25, $x + 28, $y + 54, $warning, $font, 'DOCUMENTO NO CONTROLADO / UNCONTROLLED DOCUMENT');
                    imagettftext($image, max(15, (int) ($width / 85)), -25, $x + 58, $y + 92, $warning, $font, 'SOLO PARA CONSULTA / FOR REFERENCE ONLY');
                }
            }

            imagettftext($image, max(14, (int) ($width / 95)), 0, 28, $height - 30, $small, $font, 'IGNA Studio · Solo para consulta / For reference only');

            return;
        }

        for ($y = 30; $y < $height; $y += 120) {
            for ($x = 20; $x < $width; $x += 260) {
                imagestring($image, 5, $x, $y, 'IGNA STUDIO', $primary);
                imagestring($image, 3, $x, $y + 24, 'NO CONTROLADO / UNCONTROLLED', $warning);
                imagestring($image, 3, $x, $y + 44, 'SOLO CONSULTA / REFERENCE ONLY', $warning);
            }
        }
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
