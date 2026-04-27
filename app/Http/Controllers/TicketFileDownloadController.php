<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketFile;
use App\Services\Files\GoogleDriveFileManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TicketFileDownloadController extends Controller
{
    public function __construct(private readonly GoogleDriveFileManager $googleDriveFileManager) {}

    public function admin(Ticket $ticket, TicketFile $file): RedirectResponse|StreamedResponse
    {
        abort_unless($file->ticket_id === $ticket->id, 404);

        return $this->download($file);
    }

    public function client(Request $request, Ticket $ticket, TicketFile $file): RedirectResponse|StreamedResponse
    {
        abort_unless($ticket->client_user_id === $request->user()->id, 404);
        abort_unless($file->ticket_id === $ticket->id && $file->is_client_visible, 404);

        return $this->download($file);
    }

    public function tracking(Request $request, Ticket $ticket, TicketFile $file): RedirectResponse|StreamedResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless(hash_equals($request->query('email_hash', ''), hash('sha256', strtolower($ticket->email))), 404);
        abort_unless($file->ticket_id === $ticket->id && $file->is_client_visible, 404);

        return $this->download($file);
    }

    private function download(TicketFile $file): RedirectResponse|StreamedResponse
    {
        if ($file->storage_provider === 'google_drive' && $file->google_drive_file_id) {
            try {
                return $this->googleDriveFileManager->downloadDriveFile($file);
            } catch (Throwable $exception) {
                report($exception);

                abort(404, __('site.file_not_available'));
            }
        }

        if ($file->google_drive_url) {
            abort_unless($this->isTrustedExternalFileUrl($file->google_drive_url), 404);

            return redirect()->away($file->google_drive_url);
        }

        if (! $file->storage_disk || ! $file->storage_path || ! Storage::disk($file->storage_disk)->exists($file->storage_path)) {
            abort(404, __('site.file_not_available'));
        }

        return Storage::disk($file->storage_disk)->download($file->storage_path, $file->original_name);
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
