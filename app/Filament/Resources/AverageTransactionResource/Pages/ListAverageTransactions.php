<?php

namespace App\Filament\Resources\AverageTransactionResource\Pages;

use App\Filament\Resources\AverageTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAverageTransactions extends ListRecords
{
    protected static string $resource = AverageTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
