<?php

namespace App\Notifications;

use App\Support\Settings\BrandSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('site.email_password_reset_subject'))
            ->view('emails.password-reset', [
                'brand' => app(BrandSettings::class)->publicPayload(),
                'resetUrl' => route('password.reset', [
                    'token' => $this->token,
                    'email' => $notifiable->email,
                ]),
                'supportEmail' => config('mail.reply_to.address') ?: config('mail.from.address'),
                'user' => $notifiable,
            ]);
    }
}
