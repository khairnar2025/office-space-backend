<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SystemNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectText;
    public $title;
    public $emailContent;
    public $ctaText;
    public $ctaUrl;
    public $user;
    public $recipientName;

    public function __construct($user, $subjectText, $title, $emailContent, $ctaText = null, $ctaUrl = null, $recipientName = null)
    {
        $this->user = $user;
        $this->subjectText = $subjectText;
        $this->title = $title;
        $this->emailContent = $emailContent;
        $this->ctaText = $ctaText;
        $this->ctaUrl = $ctaUrl;
        $this->recipientName = $recipientName ?? ($user->name ?? 'User');
    }

    public function build()
    {
        return $this->subject($this->subjectText)
            ->view('emails.system-notification');
    }
}
