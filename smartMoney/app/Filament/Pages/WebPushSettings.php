<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class WebPushSettings extends Page
{
        protected static ?string $navigationLabel = 'Web Push';
    // protected static Heroicon|string|null $navigationIcon = Heroicon::OutlinedBellAlert;
    // protected static ?string $navigationGroup = 'Settings';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected string $view = 'filament.pages.web-push-settings';
protected static ?int $navigationSort = 4;

    public function getVapidPublicKey(): string
    {
        return (string) config('webpush.vapid.public_key');
    }

}
