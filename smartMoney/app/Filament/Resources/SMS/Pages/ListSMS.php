<?php

namespace App\Filament\Resources\SMS\Pages;

use App\Filament\Resources\SMS\SMSResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSMS extends ListRecords
{
    protected static string $resource = SMSResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
