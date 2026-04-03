<?php

namespace App\Filament\Resources\SMSSenders\Pages;

use App\Filament\Resources\SMSSenders\SMSSenderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSMSSender extends EditRecord
{
    protected static string $resource = SMSSenderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
