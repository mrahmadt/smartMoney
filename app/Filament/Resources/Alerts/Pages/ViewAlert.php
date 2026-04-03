<?php

namespace App\Filament\Resources\Alerts\Pages;

use App\Filament\Resources\Alerts\AlertResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAlert extends ViewRecord
{
    protected static string $resource = AlertResource::class;

    public function mount(int | string $record): void
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
