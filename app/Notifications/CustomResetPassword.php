<?php
// app/Notifications/CustomResetPassword.php
namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends ResetPasswordNotification
{
    public function toMail($notifiable)
    {
        // Mobile app reset URL (frontend handles it)
        $url = config('frontend.url') . "/reset-password?token={$this->token}&email={$notifiable->email}";
        return (new MailMessage)
            ->subject('Reset Your Password')
            ->line('You requested to reset your password.')
            ->action('Reset Password', $url)
            ->line('If you did not request this, ignore this email.');
    }
}
