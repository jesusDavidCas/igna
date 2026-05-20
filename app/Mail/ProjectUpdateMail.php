<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Support\Settings\BrandSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectUpdateMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public string $type,
        public string $headline,
        public ?string $message = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('site.email_project_update_subject', ['ticket' => $this->ticket->ticket_code]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.project-update',
            with: [
                'brand' => app(BrandSettings::class)->publicPayload(),
                'trackingUrl' => route('tracking.index'),
            ],
        );
    }
}
