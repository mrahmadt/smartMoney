<?php

namespace App\Filament\Resources\CurrencyMaps\Pages;

use App\Filament\Resources\CurrencyMaps\CurrencyMapResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCurrencyMap extends EditRecord
{
    protected static string $resource = CurrencyMapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
