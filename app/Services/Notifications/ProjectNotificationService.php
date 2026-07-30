<?php

namespace App\Services\Notifications;

use App\Enums\UserRole;
use App\Mail\AdminNewTicketMail;
use App\Mail\ProjectUpdateMail;
use App\Mail\TicketDocumentUploadedAdminMail;
use App\Mail\TicketDocumentUploadedClientMail;
use App\Models\Service;
use App\Models\ServiceStage;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketFile;
use App\Models\User;
use App\Support\Locales\RecipientLocaleResolver;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ProjectNotificationService
{
    public function __construct(private readonly RecipientLocaleResolver $localeResolver) {}

    public function notifyTicket(
        Ticket $ticket,
        string $type,
        string $headlineKey,
        array $headlineReplacements = [],
        ?string $messageKey = null,
        array $messageReplacements = [],
        ?string $message = null,
    ): void
    {
        if (! filter_var($ticket->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $locale = $this->localeResolver->forTicketClient($ticket);
        $headlineReplacements = $this->localizedReplacements($headlineReplacements, $locale);
        $messageReplacements = $this->localizedReplacements($messageReplacements, $locale);

        try {
            Mail::to($ticket->email)->locale($locale)->send((new ProjectUpdateMail(
                ticket: $ticket->fresh(['currentStage', 'stageEvents.serviceStage']) ?? $ticket,
                type: $type,
                headline: trans($headlineKey, $headlineReplacements, $locale),
                message: $message ?? ($messageKey ? trans($messageKey, $messageReplacements, $locale) : null),
            ))->locale($locale));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function notifyAdminsNewTicket(Ticket $ticket): void
    {
        try {
            foreach ($this->allAdminRecipients() as $recipient) {
                Mail::to($recipient['email'])->locale($recipient['locale'])->send((new AdminNewTicketMail(
                    ticket: $ticket->fresh(['service', 'currentStage']) ?? $ticket,
                ))->locale($recipient['locale']));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function notifyAdminsDocumentSubmitted(Ticket $ticket, TicketFile $file): void
    {
        try {
            foreach ($this->adminRecipients($ticket) as $recipient) {
                $locale = $recipient['locale'];

                Mail::to($recipient['email'])->locale($locale)->send((new TicketDocumentUploadedAdminMail(
                    ticket: $ticket->fresh(['client', 'service', 'currentStage']) ?? $ticket,
                    file: $file,
                    category: $this->localizedTicketFileValue($file, 'ticket_file_category', (string) $file->deliverable_type, $locale),
                    source: $this->localizedTicketFileValue($file, 'ticket_file_source', (string) $file->upload_source, $locale),
                ))->locale($locale));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function notifyClientDocumentSubmitted(Ticket $ticket, TicketFile $file): void
    {
        $recipient = $this->clientDocumentRecipient($ticket, $file);

        if (! $recipient) {
            return;
        }

        try {
            Mail::to($recipient['email'])->locale($recipient['locale'])->send((new TicketDocumentUploadedClientMail(
                ticket: $ticket->fresh(['client', 'service', 'currentStage']) ?? $ticket,
                file: $file,
                category: $this->localizedTicketFileValue($file, 'ticket_file_category', (string) $file->deliverable_type, $recipient['locale']),
            ))->locale($recipient['locale']));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function adminRecipients(?Ticket $ticket = null): array
    {
        if ($ticket) {
            $responsible = $this->responsibleAdminRecipients($ticket);

            if ($responsible !== []) {
                return $responsible;
            }
        }

        return $this->supportRecipients();
    }

    private function allAdminRecipients(): array
    {
        $recipients = User::query()
            ->whereIn('role', [UserRole::SUPER_ADMIN, UserRole::ADMIN])
            ->where('is_active', true)
            ->get(['email', 'preferred_language'])
            ->filter(fn (User $user): bool => filter_var($user->email, FILTER_VALIDATE_EMAIL))
            ->mapWithKeys(fn (User $user): array => [
                strtolower($user->email) => [
                    'email' => $user->email,
                    'locale' => $this->localeResolver->forAdmin($user),
                ],
            ])
            ->all();

        return [
            ...$recipients,
            ...$this->supportRecipients(),
        ];
    }

    private function responsibleAdminRecipients(Ticket $ticket): array
    {
        $ticket->loadMissing([
            'stageEvents.changedBy',
            'stageEvents.supersededBy',
            'stageAudits.actor',
            'files.uploadedBy',
            'files.firstAdminDownloadedBy',
            'files.reviewedBy',
            'files.rejectedBy',
        ]);

        $admins = collect()
            ->merge($ticket->stageEvents->pluck('changedBy'))
            ->merge($ticket->stageEvents->pluck('supersededBy'))
            ->merge($ticket->stageAudits->pluck('actor'))
            ->merge($ticket->files->where('upload_source', 'admin')->pluck('uploadedBy'))
            ->merge($ticket->files->pluck('firstAdminDownloadedBy'))
            ->merge($ticket->files->pluck('reviewedBy'))
            ->merge($ticket->files->pluck('rejectedBy'))
            ->filter(fn (?User $user): bool => $user instanceof User
                && $user->is_active
                && $user->role?->canAccessAdmin()
                && filter_var($user->email, FILTER_VALIDATE_EMAIL))
            ->mapWithKeys(fn (User $user): array => [
                strtolower($user->email) => [
                    'email' => $user->email,
                    'locale' => $this->localeResolver->forAdmin($user),
                ],
            ])
            ->all();

        return $admins;
    }

    private function supportRecipients(): array
    {
        $recipients = [];

        $supportEmail = Setting::query()->where('key', 'support_email')->value('value') ?: config('mail.from.address');

        if (filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
            $recipients[strtolower($supportEmail)] = [
                'email' => $supportEmail,
                'locale' => $this->localeResolver->forAdmin(),
            ];
        }

        return $recipients;
    }

    private function clientDocumentRecipient(Ticket $ticket, TicketFile $file): ?array
    {
        if ($file->upload_source === 'authenticated_client') {
            $file->loadMissing('uploadedBy');
            $client = $file->uploadedBy;

            if (! $client || ! filter_var($client->email, FILTER_VALIDATE_EMAIL)) {
                return null;
            }

            return [
                'email' => $client->email,
                'locale' => $this->localeResolver->normalize(
                    $client->preferred_language,
                    $ticket->preferred_language,
                    $this->localeResolver->applicationFallback(),
                ),
            ];
        }

        if (! filter_var($ticket->email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return [
            'email' => $ticket->email,
            'locale' => $this->localeResolver->forTicketClient($ticket),
        ];
    }

    private function localizedReplacements(array $replacements, string $locale): array
    {
        return collect($replacements)
            ->map(function (mixed $value) use ($locale): mixed {
                if ($value instanceof ServiceStage) {
                    $key = "stages.{$value->code}";
                    $translated = trans($key, [], $locale);

                    return $translated === $key ? $value->name : $translated;
                }

                if ($value instanceof Service) {
                    $key = "services.catalog.{$value->code}.name";
                    $translated = trans($key, [], $locale);

                    return $translated === $key ? $value->name : $translated;
                }

                return $value;
            })
            ->all();
    }

    private function localizedTicketFileValue(TicketFile $file, string $prefix, string $value, string $locale): string
    {
        $key = "site.{$prefix}_{$value}";
        $translated = trans($key, [], $locale);

        if ($translated !== $key) {
            return $translated;
        }

        return $prefix === 'ticket_file_category'
            ? $file->categoryLabel()
            : $file->uploadSourceLabel();
    }
}
