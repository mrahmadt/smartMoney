<?php

namespace App\Filament\Resources\SMS\Pages;

use App\Filament\Resources\SMS\SMSResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSMS extends EditRecord
{
    protected static string $resource = SMSResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
