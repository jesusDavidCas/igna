<?php

namespace App\Services\Notifications;

use App\Enums\UserRole;
use App\Mail\AdminNewTicketMail;
use App\Mail\ProjectUpdateMail;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
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

    public function notifyAdminsNewTicket(Ticket $ticket): void
    {
        $previousLocale = App::getLocale();

        try {
            foreach ($this->adminRecipients() as $email => $locale) {
                App::setLocale($locale ?: config('app.locale'));

                Mail::to($email)->send(new AdminNewTicketMail(
                    ticket: $ticket->fresh(['service', 'currentStage']) ?? $ticket,
                ));
            }
        } catch (Throwable $exception) {
            report($exception);
        } finally {
            App::setLocale($previousLocale);
        }
    }

    private function adminRecipients(): array
    {
        $recipients = User::query()
            ->whereIn('role', [UserRole::SUPER_ADMIN, UserRole::ADMIN])
            ->where('is_active', true)
            ->get(['email', 'preferred_language'])
            ->filter(fn (User $user): bool => filter_var($user->email, FILTER_VALIDATE_EMAIL))
            ->mapWithKeys(fn (User $user): array => [strtolower($user->email) => $user->preferred_language ?: config('app.locale')])
            ->all();

        $supportEmail = Setting::query()->where('key', 'support_email')->value('value') ?: config('mail.from.address');

        if (filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
            $recipients[strtolower($supportEmail)] = config('app.locale');
        }

        return $recipients;
    }
}
