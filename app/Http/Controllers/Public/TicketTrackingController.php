<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\TrackTicketRequest;
use App\Models\Ticket;
use Illuminate\Contracts\View\View;

class TicketTrackingController extends Controller
{
    public function index(): View
    {
        $ticket = null;
        $lookup = session('tracking_lookup');

        if (is_array($lookup)) {
            $ticket = $this->resolveTicket($lookup['ticket_code'], $lookup['email']);
        }

        return view('public.tracking', [
            'ticket' => $ticket,
        ]);
    }

    public function show(TrackTicketRequest $request): View
    {
        $ticket = $this->resolveTicket(
            $request->validated('ticket_code'),
            $request->validated('email'),
        );

        return view('public.tracking', [
            'ticket' => $ticket,
        ]);
    }

    private function resolveTicket(string $ticketCode, string $email): ?Ticket
    {
        return Ticket::query()
            ->with([
                'service',
                'currentStage',
                'stageEvents.serviceStage',
                'files' => fn ($query) => $query->where('is_client_visible', true),
            ])
            ->where('ticket_code', strtoupper($ticketCode))
            ->where('email', $email)
            ->first();
    }
}
