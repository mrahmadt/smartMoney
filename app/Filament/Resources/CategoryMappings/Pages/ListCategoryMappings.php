<?php

namespace App\Filament\Resources\CategoryMappings\Pages;

use App\Filament\Resources\CategoryMappings\CategoryMappingResource;
use App\Models\Category;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCategoryMappings extends ListRecords
{
    protected static string $resource = CategoryMappingResource::class;

    public function mount(): void
    {
        app()->setLocale(auth()->user()->language ?? 'en');
        Category::syncFromFirefly();
        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
