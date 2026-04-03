<?php

namespace App\Filament\Resources\CategoryMappings\Pages;

use App\Filament\Resources\CategoryMappings\CategoryMappingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCategoryMapping extends EditRecord
{
    protected static string $resource = CategoryMappingResource::class;

    public function mount(int|string $record): void
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
