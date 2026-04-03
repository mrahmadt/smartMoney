<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SMS\SMSResource;
use App\Models\SMS;
use Filament\Widgets\Widget;

class InvalidSMSBanner extends Widget
{
    protected string $view = 'filament.widgets.invalid-sms-banner';

    protected int | string | array $columnSpan = 'full';

    public int $invalidCount = 0;
    public string $url = '';

    public function mount(): void
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        $this->invalidCount = SMS::where('is_valid', false)->count();
        $this->url = SMSResource::getUrl('index') . '?' . http_build_query(['filters' => ['is_valid' => ['value' => '0']]]);
    }
}
