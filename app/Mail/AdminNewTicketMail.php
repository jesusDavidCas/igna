<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Support\Settings\BrandSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNewTicketMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Ticket $ticket,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('site.email_admin_new_ticket_subject', ['ticket' => $this->ticket->ticket_code]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-new-ticket',
            with: [
                'brand' => app(BrandSettings::class)->publicPayload(),
                'adminTicketUrl' => route('admin.tickets.show', $this->ticket),
            ],
        );
    }
}
