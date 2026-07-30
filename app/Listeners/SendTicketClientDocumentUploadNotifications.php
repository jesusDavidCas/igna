<?php

namespace App\Listeners;

use App\Events\TicketClientDocumentUploaded;
use App\Models\Ticket;
use App\Models\TicketFile;
use App\Services\Notifications\ProjectNotificationService;
use Throwable;

class SendTicketClientDocumentUploadNotifications
{
    public function __construct(private readonly ProjectNotificationService $notifications) {}

    public function handle(TicketClientDocumentUploaded $event): void
    {
        try {
            $ticket = Ticket::query()
                ->with(['client', 'service', 'currentStage'])
                ->find($event->ticketId);
            $file = TicketFile::query()->find($event->ticketFileId);

            if (! $ticket || ! $file || $file->ticket_id !== $ticket->id || ! $file->isClientSubmitted()) {
                return;
            }

            $this->notifications->notifyClientDocumentSubmitted($ticket, $file);
            $this->notifications->notifyAdminsDocumentSubmitted($ticket, $file);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
