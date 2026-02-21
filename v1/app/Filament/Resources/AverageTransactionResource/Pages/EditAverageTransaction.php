<?php

namespace App\Filament\Resources\AverageTransactionResource\Pages;

use App\Filament\Resources\AverageTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAverageTransaction extends EditRecord
{
    protected static string $resource = AverageTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
