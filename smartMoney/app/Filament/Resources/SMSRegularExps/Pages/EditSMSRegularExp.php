<?php

namespace App\Filament\Resources\SMSRegularExps\Pages;

use App\Filament\Resources\SMSRegularExps\SMSRegularExpResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSMSRegularExp extends EditRecord
{
    protected static string $resource = SMSRegularExpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
