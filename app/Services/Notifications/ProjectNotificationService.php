<?php

namespace App\Services\Notifications;

use App\Enums\UserRole;
use App\Mail\AdminNewTicketMail;
use App\Mail\ProjectUpdateMail;
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
            foreach ($this->adminRecipients() as $recipient) {
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
            foreach ($this->adminRecipients() as $recipient) {
                $locale = $recipient['locale'];
                $categoryKey = "site.ticket_file_category_{$file->deliverable_type}";
                $sourceKey = "site.ticket_file_source_{$file->upload_source}";
                $category = trans($categoryKey, [], $locale);
                $source = trans($sourceKey, [], $locale);

                Mail::to($recipient['email'])->locale($locale)->send((new AdminNewTicketMail(
                    ticket: $ticket->fresh(['service', 'currentStage']) ?? $ticket,
                    subjectKey: 'site.email_admin_document_submitted_subject',
                    preheaderKey: 'site.email_admin_document_submitted_preheader',
                    titleKey: 'site.email_admin_document_submitted_title',
                    headlineKey: 'site.email_admin_document_submitted_headline',
                    messageKey: 'site.email_admin_document_submitted_message',
                    messageReplacements: [
                        'category' => $category === $categoryKey ? $file->categoryLabel() : $category,
                        'source' => $source === $sourceKey ? $file->uploadSourceLabel() : $source,
                    ],
                ))->locale($locale));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function adminRecipients(): array
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

        $supportEmail = Setting::query()->where('key', 'support_email')->value('value') ?: config('mail.from.address');

        if (filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
            $recipients[strtolower($supportEmail)] = [
                'email' => $supportEmail,
                'locale' => $this->localeResolver->forAdmin(),
            ];
        }

        return $recipients;
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
}
