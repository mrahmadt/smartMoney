<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlertEmail extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
        public ?string $actionText = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->title);

        $lines = array_filter(explode("\n", $this->body), fn ($line) => trim($line) !== '');
        foreach ($lines as $line) {
            $mail->line($line);
        }

        if ($this->url) {
            $mail->action($this->actionText ?? __('alert.view_details'), $this->url);
        }

        return $mail;
    }
}
