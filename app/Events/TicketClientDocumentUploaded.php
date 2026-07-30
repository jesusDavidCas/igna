<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketClientDocumentUploaded implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $ticketId,
        public readonly int $ticketFileId,
        public readonly string $uploadSource,
        public readonly ?int $uploadedByUserId = null,
    ) {}
}
