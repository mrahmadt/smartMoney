<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class WebPushPrompt extends Widget
{
    protected string $view = 'filament.widgets.web-push-prompt';

    protected int | string | array $columnSpan = 'full';

    public string $vapidPublicKey = '';

    public function mount(): void
    {
        app()->setLocale(Auth::user()->language ?? 'en');
        $this->vapidPublicKey = (string) config('webpush.vapid.public_key');
        $this->vapidPublicKey = (string) config('webpush.vapid.public_key');
    }
}
