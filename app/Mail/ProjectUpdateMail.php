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

    public ?string $updateMessage;

    public function __construct(
        public Ticket $ticket,
        public string $type,
        public string $headline,
        ?string $message = null,
    ) {
        $this->updateMessage = $message;
    }

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
                'supportEmail' => config('mail.reply_to.address') ?: config('mail.from.address'),
                'trackingUrl' => route('tracking.index'),
            ],
        );
    }
}
