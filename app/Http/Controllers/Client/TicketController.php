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
            'files' => fn ($query) => $query->where('is_client_visible', true),
        ]);

        return view('client.tickets.show', [
            'ticket' => $ticket,
        ]);
    }
}
