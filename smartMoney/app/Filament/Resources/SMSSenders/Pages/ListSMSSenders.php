<?php

namespace App\Filament\Resources\SMSSenders\Pages;

use App\Filament\Resources\SMSSenders\SMSSenderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSMSSenders extends ListRecords
{
    protected static string $resource = SMSSenderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
