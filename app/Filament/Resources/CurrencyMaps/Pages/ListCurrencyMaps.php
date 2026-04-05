<?php

namespace App\Filament\Resources\CurrencyMaps\Pages;

use App\Filament\Resources\CurrencyMaps\CurrencyMapResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCurrencyMaps extends ListRecords
{
    protected static string $resource = CurrencyMapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
