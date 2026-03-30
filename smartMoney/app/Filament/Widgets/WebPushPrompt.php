<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class WebPushPrompt extends Widget
{
    protected string $view = 'filament.widgets.web-push-prompt';

    protected int | string | array $columnSpan = 'full';

    public bool $hasSubscription = false;
    public string $vapidPublicKey = '';

    public function mount(): void
    {
        app()->setLocale(Auth::user()->language ?? 'en');
        $this->hasSubscription = Auth::user()->pushSubscriptions()->exists();
        $this->vapidPublicKey = (string) config('webpush.vapid.public_key');
    }
}
