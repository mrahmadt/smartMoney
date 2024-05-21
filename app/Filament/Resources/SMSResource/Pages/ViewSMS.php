<?php

namespace App\Filament\Resources\SMSResource\Pages;

use App\Filament\Resources\SMSResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSMS extends ViewRecord
{
    protected static string $resource = SMSResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
        ];
    }
}
