<?php

namespace App\Services\Notifications;

use App\Mail\ProjectUpdateMail;
use App\Models\Ticket;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ProjectNotificationService
{
    public function notifyTicket(Ticket $ticket, string $type, string $headline, ?string $message = null): void
    {
        if (! filter_var($ticket->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $previousLocale = App::getLocale();

        try {
            App::setLocale($ticket->preferred_language ?: config('app.locale'));

            Mail::to($ticket->email)->send(new ProjectUpdateMail(
                ticket: $ticket->fresh(['currentStage', 'stageEvents.serviceStage']) ?? $ticket,
                type: $type,
                headline: $headline,
                message: $message,
            ));
        } catch (Throwable $exception) {
            report($exception);
        } finally {
            App::setLocale($previousLocale);
        }
    }
}
