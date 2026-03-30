<?php

namespace App\Filament\Resources\Alerts\Pages;

use App\Filament\Resources\Alerts\AlertResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAlerts extends ListRecords
{
    protected static string $resource = AlertResource::class;

    public function mount(): void
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
