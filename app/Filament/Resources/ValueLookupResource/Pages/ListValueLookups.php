<?php

namespace App\Filament\Resources\ValueLookupResource\Pages;

use App\Filament\Resources\ValueLookupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListValueLookups extends ListRecords
{
    protected static string $resource = ValueLookupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
