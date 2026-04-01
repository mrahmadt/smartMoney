<?php

namespace App\Filament\Resources\CategoryMappings\Pages;

use App\Filament\Resources\CategoryMappings\CategoryMappingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategoryMapping extends CreateRecord
{
    protected static string $resource = CategoryMappingResource::class;
}
