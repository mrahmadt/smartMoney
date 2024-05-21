<?php

namespace App\Filament\Resources\ValueLookupResource\Pages;

use App\Filament\Resources\ValueLookupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditValueLookup extends EditRecord
{
    protected static string $resource = ValueLookupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
