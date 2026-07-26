<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Contracts\View\View;

class TicketController extends Controller
{
    public function show(Ticket $ticket): View
    {
        abort_unless($ticket->client_user_id === request()->user()->id, 404);

        $ticket->load([
            'service',
            'currentStage',
            'stageEvents.serviceStage',
            'files' => fn ($query) => $query->clientVisible(),
            'deliverables.files' => fn ($query) => $query->clientVisible(),
        ]);

        $submittedFiles = $ticket->files()
            ->clientSubmitted()
            ->where(function ($query) use ($ticket): void {
                $query
                    ->where('uploaded_by_user_id', request()->user()->id)
                    ->orWhere('submitted_context_hash', hash('sha256', strtolower($ticket->email)));
            })
            ->latest('uploaded_at')
            ->get();

        return view('client.tickets.show', [
            'ticket' => $ticket,
            'submittedFiles' => $submittedFiles,
        ]);
    }
}
