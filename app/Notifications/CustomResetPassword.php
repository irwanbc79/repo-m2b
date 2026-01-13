<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword; // Kita pakai induknya

class CustomResetPassword extends ResetPassword
{
    use Queueable;

    // Kita override (timpa) fungsi pembuatan emailnya
    public function toMail($notifiable)
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        // GUNAKAN VIEW KUSTOM (Bukan MailMessage standar)
        return (new MailMessage)
            ->subject('Permintaan Reset Password - M2B Portal')
            ->view('emails.reset-email', [
                'url' => $url,
                'user' => $notifiable
            ]);
    }
}