<?php

namespace App\Support\Tickets;

use App\Models\Ticket;

class TicketCodeGenerator
{
    public function generate(): string
    {
        $year = now()->format('Y');

        $latestCode = Ticket::query()
            ->where('ticket_code', 'like', "IGNA-{$year}-%")
            ->latest('id')
            ->value('ticket_code');

        $sequence = $latestCode
            ? ((int) str($latestCode)->afterLast('-')->toString()) + 1
            : 1;

        return sprintf('IGNA-%s-%05d', $year, $sequence);
    }
}
