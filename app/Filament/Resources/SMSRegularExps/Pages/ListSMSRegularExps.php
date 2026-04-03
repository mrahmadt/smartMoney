<?php

namespace App\Filament\Resources\SMSRegularExps\Pages;

use App\Filament\Resources\SMSRegularExps\SMSRegularExpResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSMSRegularExps extends ListRecords
{
    protected static string $resource = SMSRegularExpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
