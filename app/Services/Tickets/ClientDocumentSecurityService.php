<?php

namespace App\Services\Tickets;

use App\Models\Ticket;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClientDocumentSecurityService
{
    private const MAX_BYTES = 2 * 1024 * 1024;
    private const MAX_IMAGE_WIDTH = 8000;
    private const MAX_IMAGE_HEIGHT = 8000;
    private const MAX_IMAGE_PIXELS = 25000000;
    private const MIME_BY_EXTENSION = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    /**
     * @return array{original_name:string,stored_name:string,mime_type:string,size_bytes:int,storage_provider:string,storage_disk:string,storage_path:string,google_drive_file_id:null,google_drive_url:null}
     */
    public function store(Ticket $ticket, UploadedFile $file, string $source): array
    {
        $this->inspect($file, $ticket, $source);

        $extension = $this->extension($file);
        $detectedMime = $this->detectedMime($file);
        $storedExtension = $extension === 'jpeg' ? 'jpg' : $extension;
        $storedName = Str::uuid()->toString().'.'.$storedExtension;
        $storagePath = "client-documents/tickets/{$ticket->ticket_code}/{$storedName}";

        if (str_starts_with($detectedMime, 'image/')) {
            $contents = $this->cleanImageContents($file, $detectedMime);
        } else {
            $contents = file_get_contents($file->getRealPath());
        }

        if ($contents === false || $contents === '') {
            $this->reject('unreadable_contents', $file, $ticket, $source);
        }

        Storage::disk('local')->put($storagePath, $contents);

        return [
            'original_name' => $this->sanitizeOriginalName($file->getClientOriginalName()),
            'stored_name' => $storedName,
            'mime_type' => $detectedMime,
            'size_bytes' => Storage::disk('local')->size($storagePath),
            'storage_provider' => 'local_quarantine',
            'storage_disk' => 'local',
            'storage_path' => $storagePath,
            'google_drive_file_id' => null,
            'google_drive_url' => null,
        ];
    }

    public function inspect(UploadedFile $file, Ticket $ticket, string $source): void
    {
        if (! $file->isValid()) {
            $this->reject('upload_not_valid', $file, $ticket, $source);
        }

        if ($file->getSize() === false || $file->getSize() > self::MAX_BYTES) {
            $this->reject('file_too_large', $file, $ticket, $source);
        }

        $originalName = $file->getClientOriginalName();

        if (str_contains($originalName, "\0")) {
            $this->reject('null_byte_filename', $file, $ticket, $source);
        }

        $extension = $this->extension($file);

        if (! array_key_exists($extension, self::MIME_BY_EXTENSION)) {
            $this->reject('extension_not_allowed', $file, $ticket, $source);
        }

        if (str_contains(pathinfo($originalName, PATHINFO_FILENAME), '.')) {
            $this->reject('double_extension', $file, $ticket, $source);
        }

        $detectedMime = $this->detectedMime($file);
        $expectedMime = self::MIME_BY_EXTENSION[$extension];

        if ($detectedMime !== $expectedMime) {
            $this->reject('mime_extension_mismatch', $file, $ticket, $source);
        }

        if ($detectedMime === 'application/pdf') {
            $this->inspectPdf($file, $ticket, $source);

            return;
        }

        $this->inspectImage($file, $detectedMime, $ticket, $source);
    }

    private function inspectPdf(UploadedFile $file, Ticket $ticket, string $source): void
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if (! $handle) {
            $this->reject('pdf_unreadable', $file, $ticket, $source);
        }

        $header = fread($handle, 5);
        fclose($handle);

        if ($header !== '%PDF-') {
            $this->reject('pdf_signature_invalid', $file, $ticket, $source);
        }

        $contents = file_get_contents($file->getRealPath());

        if ($contents === false || ! str_contains(substr($contents, -2048), '%%EOF')) {
            $this->reject('pdf_truncated', $file, $ticket, $source);
        }
    }

    private function inspectImage(UploadedFile $file, string $detectedMime, Ticket $ticket, string $source): void
    {
        if (! in_array($detectedMime, ['image/jpeg', 'image/png'], true)) {
            $this->reject('image_type_invalid', $file, $ticket, $source);
        }

        $imageInfo = @getimagesize($file->getRealPath());

        if (! is_array($imageInfo)) {
            $this->reject('image_decode_failed', $file, $ticket, $source);
        }

        [$width, $height] = $imageInfo;

        if ($width <= 0 || $height <= 0 || $width > self::MAX_IMAGE_WIDTH || $height > self::MAX_IMAGE_HEIGHT || ($width * $height) > self::MAX_IMAGE_PIXELS) {
            $this->reject('image_dimensions_invalid', $file, $ticket, $source);
        }
    }

    private function cleanImageContents(UploadedFile $file, string $mimeType): string
    {
        $raw = file_get_contents($file->getRealPath());

        if ($raw === false) {
            return '';
        }

        if (! function_exists('imagecreatefromstring')) {
            return $raw;
        }

        $image = @imagecreatefromstring($raw);

        if (! $image) {
            return '';
        }

        ob_start();

        if ($mimeType === 'image/png') {
            imagepng($image);
        } else {
            imagejpeg($image, null, 90);
        }

        $contents = ob_get_clean();
        imagedestroy($image);

        return is_string($contents) ? $contents : '';
    }

    private function extension(UploadedFile $file): string
    {
        return strtolower($file->getClientOriginalExtension());
    }

    private function detectedMime(UploadedFile $file): string
    {
        $mime = $file->getMimeType();

        return is_string($mime) ? strtolower($mime) : 'application/octet-stream';
    }

    private function sanitizeOriginalName(string $name): string
    {
        $name = str_replace("\0", '', basename($name));
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $safeBase = Str::slug($base) ?: 'document';

        return $extension ? "{$safeBase}.{$extension}" : $safeBase;
    }

    private function reject(string $reason, UploadedFile $file, Ticket $ticket, string $source): never
    {
        Log::warning('Rejected client ticket document upload.', [
            'reason' => $reason,
            'ticket_id' => $ticket->id,
            'source' => $source,
            'extension' => $this->extension($file),
            'detected_mime' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        throw ValidationException::withMessages([
            'document' => __('site.file_could_not_be_verified'),
        ]);
    }
}
