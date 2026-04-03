<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class WebPush extends Notification
{
    use Queueable;

    public $title;
    public $body;
    /**
     * Create a new notification instance.
     */
    public function __construct($title, $body)
    {
        $this->title = $title;
        $this->body = $body;
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
        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->options(['TTL' => 1000])
            ;
            // ->icon('https://png.pngtree.com/png-vector/20230105/ourmid/pngtree-3d-green-check-icon-in-transparent-background-png-image_6552254.png')
            // ->action('View account', 'view_account') //, 'icon.png'
            // ->data(['id' => 1234, 'url' => 'https://google.com'])
            // ->badge(1)
            // ->image('https://pngimg.com/uploads/approved/small/approved_PNG44.png')
            // ->vibrate(5)
            // ->dir()
            // 
            // ->lang()
            // ->renotify()
            // ->requireInteraction()
            // ->tag()

    }
}
