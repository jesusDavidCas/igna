<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketFile;
use App\Models\User;
use App\Services\Files\GoogleDriveFileManager;
use App\Services\Tickets\TicketFileAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TicketFileDownloadController extends Controller
{
    public function __construct(
        private readonly GoogleDriveFileManager $googleDriveFileManager,
        private readonly TicketFileAccessService $ticketFileAccessService,
    ) {}

    public function admin(Request $request, Ticket $ticket, TicketFile $file): RedirectResponse|StreamedResponse
    {
        $this->ticketFileAccessService->assertAdminCanAccess($ticket, $file);

        return $this->download($file, $request->user());
    }

    // Client portal download: ensures the logged-in client owns the ticket,
    // and that the requested file belongs to this ticket and is marked visible to clients.
    public function client(Request $request, Ticket $ticket, TicketFile $file): RedirectResponse|StreamedResponse
    {
        $this->ticketFileAccessService->assertClientCanAccess($request->user(), $ticket, $file);

        return $this->download($file);
    }

    // Public tracking link download: verifies the URL signature, checks the hashed email
    // against the ticket's email to prevent leakages, and validates that the file is client-visible.
    public function tracking(Request $request, Ticket $ticket, TicketFile $file): RedirectResponse|StreamedResponse
    {
        $this->ticketFileAccessService->assertSignedTrackingCanAccess($request, $ticket, $file);

        return $this->download($file);
    }

    private function download(TicketFile $file, ?User $admin = null): RedirectResponse|StreamedResponse
    {
        if ($file->storage_provider === 'google_drive' && $file->google_drive_file_id) {
            try {
                $response = $this->googleDriveFileManager->downloadDriveFile($file);
                $this->recordFirstAdminDownload($file, $admin);

                return $response;
            } catch (Throwable $exception) {
                report($exception);

                abort(404, __('site.file_not_available'));
            }
        }

        if ($file->google_drive_url) {
            abort_unless($this->isTrustedExternalFileUrl($file->google_drive_url), 404);
            $this->recordFirstAdminDownload($file, $admin);

            return redirect()->away($file->google_drive_url);
        }

        if (! $file->storage_disk || ! $file->storage_path || ! Storage::disk($file->storage_disk)->exists($file->storage_path)) {
            abort(404, __('site.file_not_available'));
        }

        $this->recordFirstAdminDownload($file, $admin);

        return Storage::disk($file->storage_disk)->download($file->storage_path, $file->original_name, array_filter([
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]));
    }

    private function recordFirstAdminDownload(TicketFile $file, ?User $admin): void
    {
        if (! $admin || ! $file->isClientSubmitted() || $file->review_status !== 'pending_review') {
            return;
        }

        $file->forceFill([
            'review_status' => 'downloaded',
            'first_admin_downloaded_by_user_id' => $file->first_admin_downloaded_by_user_id ?? $admin->id,
            'first_admin_downloaded_at' => $file->first_admin_downloaded_at ?? now(),
        ])->save();
    }

    private function isTrustedExternalFileUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host)) {
            return false;
        }

        return collect(config('services.google_drive.allowed_download_hosts', []))
            ->contains(fn (string $allowedHost): bool => $host === $allowedHost || Str::endsWith($host, ".{$allowedHost}"));
    }
}
