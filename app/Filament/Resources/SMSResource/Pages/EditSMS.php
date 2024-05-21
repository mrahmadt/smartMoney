<?php

namespace App\Filament\Resources\SMSResource\Pages;

use App\Filament\Resources\SMSResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSMS extends EditRecord
{
    protected static string $resource = SMSResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
