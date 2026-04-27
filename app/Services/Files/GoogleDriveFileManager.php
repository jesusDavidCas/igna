<?php

namespace App\Services\Files;

use App\Models\Ticket;
use App\Models\TicketFile;
use Google\Client as GoogleClient;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoogleDriveFileManager
{
    private ?Drive $drive = null;

    public function storeTicketFile(Ticket $ticket, UploadedFile $file): array
    {
        if ($this->isConfigured()) {
            return $this->storeTicketFileOnDrive($ticket, $file);
        }

        $storedName = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        // Local private storage is the safe development fallback when Drive credentials are not configured.
        // TODO: Add a watermark pipeline for partial deliverables before external sharing.
        $storagePath = $file->storeAs(
            "stubs/tickets/{$ticket->ticket_code}",
            $storedName,
            'local',
        );

        return [
            'stored_name' => $storedName,
            'storage_provider' => 'local_stub',
            'storage_disk' => 'local',
            'storage_path' => $storagePath,
            'google_drive_file_id' => null,
            'google_drive_url' => null,
            'size_bytes' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
        ];
    }

    public function downloadDriveFile(TicketFile $file): StreamedResponse
    {
        if (! $file->google_drive_file_id) {
            throw new RuntimeException('The ticket file does not have a Google Drive file id.');
        }

        $response = $this->drive()->files->get($file->google_drive_file_id, [
            'alt' => 'media',
        ]);

        $contents = $response->getBody()->getContents();

        return response()->streamDownload(function () use ($contents): void {
            echo $contents;
        }, $file->original_name, array_filter([
            'Content-Type' => $file->mime_type,
            'Content-Length' => $file->size_bytes,
        ]));
    }

    public function downloadUrl(?string $storageDisk, ?string $storagePath): ?string
    {
        if (! $storageDisk || ! $storagePath) {
            return null;
        }

        return Storage::disk($storageDisk)->url($storagePath);
    }

    private function storeTicketFileOnDrive(Ticket $ticket, UploadedFile $file): array
    {
        $folderId = $this->ensureTicketFolder($ticket);
        $storedName = $this->storedDriveName($file);
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('The uploaded file could not be read before sending it to Google Drive.');
        }

        $createdFile = $this->drive()->files->create(new DriveFile([
            'name' => $storedName,
            'parents' => [$folderId],
        ]), [
            'data' => $contents,
            'mimeType' => $file->getClientMimeType() ?: 'application/octet-stream',
            'uploadType' => 'multipart',
            'fields' => 'id,name,mimeType,size,webViewLink,webContentLink',
        ]);

        return [
            'stored_name' => $createdFile->getName() ?: $storedName,
            'storage_provider' => 'google_drive',
            'storage_disk' => null,
            'storage_path' => null,
            'google_drive_file_id' => $createdFile->getId(),
            'google_drive_url' => $createdFile->getWebViewLink(),
            'size_bytes' => (int) ($createdFile->getSize() ?: $file->getSize()),
            'mime_type' => $createdFile->getMimeType() ?: $file->getClientMimeType(),
        ];
    }

    private function ensureTicketFolder(Ticket $ticket): string
    {
        if ($ticket->google_drive_folder_id) {
            return $ticket->google_drive_folder_id;
        }

        $rootFolderId = config('services.google_drive.root_folder_id');

        if (! is_string($rootFolderId) || $rootFolderId === '') {
            throw new RuntimeException('Google Drive root folder id is not configured.');
        }

        $folder = $this->drive()->files->create(new DriveFile([
            'name' => Str::limit("{$ticket->ticket_code} - {$ticket->project_name}", 140, ''),
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$rootFolderId],
        ]), [
            'fields' => 'id,webViewLink',
        ]);

        $ticket->forceFill([
            'google_drive_folder_id' => $folder->getId(),
            'google_drive_folder_url' => $folder->getWebViewLink(),
        ])->save();

        return $folder->getId();
    }

    private function storedDriveName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = Str::slug($baseName) ?: 'file';

        return Str::uuid()->toString().'-'.$slug.($extension ? ".{$extension}" : '');
    }

    private function isConfigured(): bool
    {
        $serviceAccountPath = $this->serviceAccountPath();

        return (bool) config('services.google_drive.enabled')
            && filled(config('services.google_drive.root_folder_id'))
            && $serviceAccountPath !== null
            && is_file($serviceAccountPath);
    }

    private function drive(): Drive
    {
        if ($this->drive) {
            return $this->drive;
        }

        $serviceAccountPath = $this->serviceAccountPath();

        if ($serviceAccountPath === null || ! is_file($serviceAccountPath)) {
            throw new RuntimeException('Google Drive service account JSON file is not configured or cannot be read.');
        }

        $client = new GoogleClient();
        $client->setApplicationName(config('app.name', 'IGNA Studio').' Drive Storage');
        $client->setAuthConfig($serviceAccountPath);
        $client->setScopes([Drive::DRIVE]);

        return $this->drive = new Drive($client);
    }

    private function serviceAccountPath(): ?string
    {
        $path = config('services.google_drive.service_account_json');

        if (! is_string($path) || $path === '') {
            return null;
        }

        return str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
    }
}
