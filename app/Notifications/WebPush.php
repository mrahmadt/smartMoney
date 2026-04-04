<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class WebPush extends Notification implements ShouldQueue
{
    use Queueable;

    public $title;
    public $body;
    public $url;
    public $tag;

    public function __construct($title, $body, $url = null, $tag = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->url = $url;
        $this->tag = $tag;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }


    public function toWebPush($notifiable, $notification)
    {
        $message = (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->icon('/img/icon-192x192.png')
            ->vibrate([200, 100, 200])
            ->options(['TTL' => 1000]);

        $data = [];
        if ($this->url) {
            $data['url'] = $this->url;
        }
        if (!empty($data)) {
            $message->data($data);
        }
        if ($this->tag) {
            $message->tag($this->tag);
        }

        return $message;
    }
}
