<?php

namespace App\Filament\Resources\SMSResource\Pages;

use App\Filament\Resources\SMSResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSMS extends ListRecords
{
    protected static string $resource = SMSResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
