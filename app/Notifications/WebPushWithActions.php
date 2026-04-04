<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class WebPushWithActions extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<array{label: string, action: string}>  $actions
     */
    public function __construct(
        public string $title,
        public string $body,
        public array $actions = [],
        public array $data = [],
    ) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $message = (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->icon('/img/icon-192x192.png')
            ->vibrate([200, 100, 200])
            ->options(['TTL' => 1000]);

        foreach ($this->actions as $action) {
            $message->action($action['label'], $action['action']);
        }

        if (!empty($this->data)) {
            $message->data($this->data);
        }

        return $message;
    }
}
