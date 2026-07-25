<?php

namespace App\Services\Tickets;

use App\Models\Ticket;
use App\Models\TicketFile;
use App\Models\User;
use Illuminate\Http\Request;

class TicketFileAccessService
{
    public function assertAdminCanAccess(Ticket $ticket, TicketFile $file): void
    {
        abort_unless($file->ticket_id === $ticket->id, 404);
    }

    public function assertClientCanAccess(User $client, Ticket $ticket, TicketFile $file): void
    {
        abort_unless($ticket->client_user_id === $client->id, 404);
        abort_unless(
            $this->isClientVisibleForTicket($ticket, $file) || $this->isClientSubmittedForTicket($ticket, $file, $client),
            404,
        );
    }

    public function assertSignedTrackingCanAccess(Request $request, Ticket $ticket, TicketFile $file): void
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless(hash_equals($request->query('email_hash', ''), hash('sha256', strtolower($ticket->email))), 404);
        abort_unless(
            $this->isClientVisibleForTicket($ticket, $file) || $this->isTrackingSubmittedForTicket($ticket, $file, (string) $request->query('email_hash', '')),
            404,
        );
    }

    public function isClientVisibleForTicket(Ticket $ticket, TicketFile $file): bool
    {
        if ($file->ticket_id !== $ticket->id || ! $file->is_client_visible) {
            return false;
        }

        $file->loadMissing('deliverable.serviceDeliverable');

        if (! $file->deliverable) {
            return true;
        }

        if ($file->deliverable->ticket_id !== $ticket->id) {
            return false;
        }

        return ! $file->deliverable->serviceDeliverable
            || $file->deliverable->serviceDeliverable->is_client_visible_by_default;
    }

    public function isClientSubmittedForTicket(Ticket $ticket, TicketFile $file, User $client): bool
    {
        if ($file->ticket_id !== $ticket->id || ! $file->isClientSubmitted()) {
            return false;
        }

        return $file->uploaded_by_user_id === $client->id
            || hash_equals((string) $file->submitted_context_hash, hash('sha256', strtolower($ticket->email)));
    }

    public function isTrackingSubmittedForTicket(Ticket $ticket, TicketFile $file, string $emailHash): bool
    {
        return $file->ticket_id === $ticket->id
            && $file->isClientSubmitted()
            && filled($file->submitted_context_hash)
            && hash_equals((string) $file->submitted_context_hash, $emailHash);
    }
}
