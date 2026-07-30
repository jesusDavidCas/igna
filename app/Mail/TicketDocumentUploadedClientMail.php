<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\TicketFile;
use App\Support\Settings\BrandSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketDocumentUploadedClientMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public TicketFile $file,
        public string $category,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('site.email_client_document_received_subject', ['ticket' => $this->ticket->ticket_code]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-document-uploaded-client',
            with: [
                'brand' => app(BrandSettings::class)->publicPayload(),
                'supportEmail' => config('mail.reply_to.address') ?: config('mail.from.address'),
                'trackingUrl' => route('tracking.index'),
            ],
        );
    }
}
